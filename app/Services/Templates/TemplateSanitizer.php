<?php

namespace App\Services\Templates;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Reduces untrusted template markup to the subset an email can actually use.
 *
 * Admin-authored templates are trusted, but a template generated from a user's
 * own brief is not: the brief reaches a language model, and a model can be
 * talked into emitting anything. Because template HTML is rendered into the
 * preview and can later be promoted for every user on the platform, it is
 * filtered against an allow-list rather than scanned for known-bad patterns.
 */
class TemplateSanitizer
{
    /** Tags an email client will actually render. Everything else is dropped. */
    private const ALLOWED_TAGS = [
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
        'div', 'span', 'p', 'a', 'img', 'br', 'hr', 'center',
        'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'font',
    ];

    /** Tags whose text content must go too, not just the tag itself. */
    private const STRIPPED_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'textarea', 'noscript',
        'svg', 'math', 'link', 'meta', 'base', 'title', 'head',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'style', 'width', 'height', 'align', 'valign', 'bgcolor', 'background',
        'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan',
        'href', 'src', 'alt', 'title', 'target', 'rel',
        'role', 'aria-hidden', 'aria-label', 'dir', 'lang', 'color', 'face', 'size',
    ];

    private const SAFE_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** CSS that can execute, load, or break out of the message body. */
    private const FORBIDDEN_CSS = [
        'expression', 'javascript:', 'vbscript:', 'behavior', '-moz-binding',
        '@import', 'position:fixed', 'position: fixed',
    ];

    /**
     * @return array{html: string, removed: array<int, string>}
     */
    public function sanitize(string $html): array
    {
        $removed = [];

        if (trim($html) === '') {
            return ['html' => '', 'removed' => []];
        }

        // The DOM encodes spaces inside attribute values, which would turn
        // {{ photo_url }} into {{%20photo_url%20}} and stop the renderer from
        // ever matching it. Tokens are swapped for inert alphanumeric markers
        // for the duration of the parse.
        [$html, $tokens] = $this->maskTokens($html);

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        // The fragment is wrapped so the parser does not invent html/body around
        // it, and marked UTF-8 so multibyte content survives the round trip.
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="tpl-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('tpl-root');

        if (! $root) {
            return ['html' => '', 'removed' => ['The markup could not be parsed.']];
        }

        $this->stripDangerousElements($document, $removed);
        $this->walk($root, $removed);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return [
            'html' => $this->unmaskTokens(trim($output), $tokens),
            'removed' => array_values(array_unique($removed)),
        ];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function maskTokens(string $html): array
    {
        $tokens = [];

        $masked = preg_replace_callback(
            '/\{\{[^{}]*\}\}/',
            function (array $matches) use (&$tokens): string {
                $marker = 'zzTPLTOKEN'.count($tokens).'zz';
                $tokens[$marker] = $matches[0];

                return $marker;
            },
            $html
        );

        return [$masked ?? $html, $tokens];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function unmaskTokens(string $html, array $tokens): string
    {
        return $tokens === []
            ? $html
            : str_replace(array_keys($tokens), array_values($tokens), $html);
    }

    /**
     * @param  array<int, string>  $removed
     */
    private function stripDangerousElements(DOMDocument $document, array &$removed): void
    {
        $xpath = new DOMXPath($document);
        $query = implode(' | ', array_map(
            fn (string $tag): string => "//{$tag}",
            self::STRIPPED_WITH_CONTENT
        ));

        /** @var iterable<DOMElement> $nodes */
        $nodes = iterator_to_array($xpath->query($query) ?: []);

        foreach ($nodes as $node) {
            $removed[] = "Removed a <{$node->nodeName}> element, which email clients strip anyway.";
            $node->parentNode?->removeChild($node);
        }

        // Comments can hide markup from review while surviving into the output.
        /** @var iterable<DOMNode> $comments */
        $comments = iterator_to_array($xpath->query('//comment()') ?: []);

        foreach ($comments as $comment) {
            $comment->parentNode?->removeChild($comment);
        }
    }

    /**
     * @param  array<int, string>  $removed
     */
    private function walk(DOMNode $node, array &$removed): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $removed[] = "Unwrapped a <{$tag}> element, which is not usable in email.";
                $this->unwrap($child);

                continue;
            }

            $this->cleanAttributes($child, $removed);
            $this->walk($child, $removed);
        }
    }

    /**
     * Keep the children, drop the wrapper — losing the text inside an unknown
     * tag would silently gut the template.
     */
    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * @param  array<int, string>  $removed
     */
    private function cleanAttributes(DOMElement $element, array &$removed): void
    {
        /** @var array<int, DOMAttr> $attributes */
        $attributes = iterator_to_array($element->attributes ?? []);

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue ?? '';

            // Every on* handler goes, whatever the tag.
            if (str_starts_with($name, 'on')) {
                $removed[] = "Removed an inline {$name} handler.";
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if (in_array($name, ['href', 'src', 'background'], true) && ! $this->isSafeUrl($value)) {
                $removed[] = "Removed an unsafe {$name} value.";
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if ($name === 'style') {
                $cleaned = $this->cleanStyle($value, $removed);

                if ($cleaned === '') {
                    $element->removeAttribute($attribute->nodeName);
                } else {
                    $element->setAttribute('style', $cleaned);
                }
            }
        }
    }

    /**
     * Template tokens are legal in a URL slot, since the renderer escapes and
     * scheme-checks whatever ends up replacing them.
     */
    private function isSafeUrl(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || str_contains($value, '{{')) {
            return true;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $value, $matches) === 1) {
            return in_array(strtolower($matches[1]), self::SAFE_URL_SCHEMES, true);
        }

        // Relative or protocol-relative; harmless on its own.
        return true;
    }

    /**
     * @param  array<int, string>  $removed
     */
    private function cleanStyle(string $style, array &$removed): string
    {
        $normalised = strtolower(str_replace([' ', "\t", "\n", '/*', '*/'], '', $style));

        foreach (self::FORBIDDEN_CSS as $needle) {
            if (str_contains($normalised, str_replace(' ', '', $needle))) {
                $removed[] = 'Removed a style declaration containing '.$needle.'.';

                return '';
            }
        }

        // Any url() is dropped. Background images are unreliable in email anyway,
        // and a generated template that reaches every user could otherwise beacon
        // each recipient to a third-party host.
        if (str_contains($normalised, 'url(')) {
            $removed[] = 'Removed a style declaration containing url(), which can track recipients.';

            return '';
        }

        return $style;
    }
}
