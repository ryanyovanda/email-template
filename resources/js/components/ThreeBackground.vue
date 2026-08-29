<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue';
import * as THREE from 'three';

const canvasEl = ref<HTMLCanvasElement | null>(null);
let renderer: THREE.WebGLRenderer | null = null;
let animationId = 0;
let cleanup: (() => void) | null = null;

onMounted(() => {
    const canvas = canvasEl.value;
    if (!canvas) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(
        60,
        window.innerWidth / window.innerHeight,
        0.1,
        100,
    );
    camera.position.z = 14;

    renderer = new THREE.WebGLRenderer({
        canvas,
        alpha: true,
        antialias: true,
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);

    // --- Floating gradient blobs (icosahedron w/ soft shader-ish material) ---
    const blobConfigs = [
        { color: 0x8b5cf6, pos: [-6, 3, -2], size: 3.2 },
        { color: 0x3b82f6, pos: [6, -2, -1], size: 2.8 },
        { color: 0xec4899, pos: [3, 4, -4], size: 2.4 },
        { color: 0x10b981, pos: [-5, -4, -3], size: 2.6 },
        { color: 0xf59e0b, pos: [0, -1, -2], size: 2.0 },
    ];

    const blobs: THREE.Mesh[] = [];
    blobConfigs.forEach((cfg) => {
        const geo = new THREE.IcosahedronGeometry(cfg.size, 6);
        const mat = new THREE.MeshBasicMaterial({
            color: cfg.color,
            transparent: true,
            opacity: 0.55,
        });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.set(cfg.pos[0], cfg.pos[1], cfg.pos[2]);
        mesh.userData = {
            baseX: cfg.pos[0],
            baseY: cfg.pos[1],
            speed: 0.3 + Math.random() * 0.4,
            phase: Math.random() * Math.PI * 2,
        };
        scene.add(mesh);
        blobs.push(mesh);
    });

    // --- Particle field ---
    const particleCount = 700;
    const positions = new Float32Array(particleCount * 3);
    for (let i = 0; i < particleCount; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 40;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 30;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 20;
    }
    const particleGeo = new THREE.BufferGeometry();
    particleGeo.setAttribute(
        'position',
        new THREE.BufferAttribute(positions, 3),
    );
    const particleMat = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.05,
        transparent: true,
        opacity: 0.5,
    });
    const particles = new THREE.Points(particleGeo, particleMat);
    scene.add(particles);

    // --- Mouse parallax ---
    let mouseX = 0;
    let mouseY = 0;
    let targetX = 0;
    let targetY = 0;
    const onMouseMove = (e: MouseEvent) => {
        mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
        mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    };
    window.addEventListener('mousemove', onMouseMove);

    // --- Resize ---
    const onResize = () => {
        if (!renderer) return;
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    };
    window.addEventListener('resize', onResize);

    // --- Animation loop ---
    const clock = new THREE.Clock();
    const animate = () => {
        const t = clock.getElapsedTime();

        blobs.forEach((b) => {
            const d = b.userData;
            b.position.x = d.baseX + Math.sin(t * d.speed + d.phase) * 1.2;
            b.position.y = d.baseY + Math.cos(t * d.speed + d.phase) * 1.0;
            b.rotation.x = t * 0.1;
            b.rotation.y = t * 0.15;
        });

        particles.rotation.y = t * 0.02;

        targetX += (mouseX - targetX) * 0.05;
        targetY += (mouseY - targetY) * 0.05;
        camera.position.x = targetX * 1.5;
        camera.position.y = -targetY * 1.5;
        camera.lookAt(scene.position);

        renderer!.render(scene, camera);
        animationId = requestAnimationFrame(animate);
    };
    animate();

    cleanup = () => {
        cancelAnimationFrame(animationId);
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('resize', onResize);
        blobs.forEach((b) => {
            b.geometry.dispose();
            (b.material as THREE.Material).dispose();
        });
        particleGeo.dispose();
        particleMat.dispose();
        renderer?.dispose();
    };
});

onBeforeUnmount(() => {
    cleanup?.();
});
</script>

<template>
    <canvas
        ref="canvasEl"
        class="pointer-events-none fixed inset-0 -z-10 h-full w-full"
    ></canvas>
</template>
