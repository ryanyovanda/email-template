<script setup lang="ts">
import * as THREE from 'three';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const canvasEl = ref<HTMLCanvasElement | null>(null);
let renderer: THREE.WebGLRenderer | null = null;
let animationId = 0;
let cleanup: (() => void) | null = null;

onMounted(() => {
    const canvas = canvasEl.value;

    if (!canvas) {
        return;
    }

    const parent = canvas.parentElement;

    const getW = () => (parent ? parent.clientWidth : window.innerWidth);
    const getH = () => (parent ? parent.clientHeight : window.innerHeight);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, getW() / getH(), 0.1, 1000);
    camera.position.z = 300;

    renderer = new THREE.WebGLRenderer({
        canvas,
        alpha: true,
        antialias: true,
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(getW(), getH());

    const globe = new THREE.Group();
    scene.add(globe);

    const RADIUS = 130;

    // --- Wireframe sphere (lat/long grid) ---
    const wireGeo = new THREE.SphereGeometry(RADIUS, 36, 24);
    const wireMat = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        wireframe: true,
        transparent: true,
        opacity: 0.16,
    });
    globe.add(new THREE.Mesh(wireGeo, wireMat));

    // --- Point nodes distributed on the sphere (fibonacci) ---
    const nodeCount = 260;
    const nodePositions: THREE.Vector3[] = [];
    const posArray = new Float32Array(nodeCount * 3);

    for (let i = 0; i < nodeCount; i++) {
        const y = 1 - (i / (nodeCount - 1)) * 2;
        const radiusAtY = Math.sqrt(1 - y * y);
        const theta = i * 2.399963229728653;
        const x = Math.cos(theta) * radiusAtY;
        const z = Math.sin(theta) * radiusAtY;
        const v = new THREE.Vector3(x, y, z).multiplyScalar(RADIUS);

        nodePositions.push(v);
        posArray[i * 3] = v.x;
        posArray[i * 3 + 1] = v.y;
        posArray[i * 3 + 2] = v.z;
    }

    const nodeGeo = new THREE.BufferGeometry();
    nodeGeo.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
    const nodeMat = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 3,
        transparent: true,
        opacity: 1,
    });
    globe.add(new THREE.Points(nodeGeo, nodeMat));

    // --- Connection arcs between nearby nodes ---
    const linePositions: number[] = [];
    const maxDist = RADIUS * 0.55;

    for (let i = 0; i < nodePositions.length; i++) {
        for (let j = i + 1; j < nodePositions.length; j++) {
            if (nodePositions[i].distanceTo(nodePositions[j]) < maxDist) {
                linePositions.push(
                    nodePositions[i].x,
                    nodePositions[i].y,
                    nodePositions[i].z,
                    nodePositions[j].x,
                    nodePositions[j].y,
                    nodePositions[j].z,
                );
            }
        }
    }

    const lineGeo = new THREE.BufferGeometry();
    lineGeo.setAttribute(
        'position',
        new THREE.BufferAttribute(new Float32Array(linePositions), 3),
    );
    const lineMat = new THREE.LineBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.3,
    });
    globe.add(new THREE.LineSegments(lineGeo, lineMat));

    // --- Outer particle halo ---
    const haloCount = 400;
    const haloArray = new Float32Array(haloCount * 3);

    for (let i = 0; i < haloCount; i++) {
        const r = RADIUS * (1.6 + Math.random() * 1.8);
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos(2 * Math.random() - 1);

        haloArray[i * 3] = r * Math.sin(phi) * Math.cos(theta);
        haloArray[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
        haloArray[i * 3 + 2] = r * Math.cos(phi);
    }

    const haloGeo = new THREE.BufferGeometry();
    haloGeo.setAttribute('position', new THREE.BufferAttribute(haloArray, 3));
    const haloMat = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 1,
        transparent: true,
        opacity: 0.4,
    });
    const halo = new THREE.Points(haloGeo, haloMat);
    scene.add(halo);

    globe.rotation.x = 0.35;

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

    const onResize = () => {
        if (!renderer) {
            return;
        }

        camera.aspect = getW() / getH();
        camera.updateProjectionMatrix();
        renderer.setSize(getW(), getH());
    };

    window.addEventListener('resize', onResize);

    const clock = new THREE.Clock();
    const animate = () => {
        const delta = clock.getDelta();

        globe.rotation.y += delta * 0.12;
        halo.rotation.y -= delta * 0.04;

        targetX += (mouseX - targetX) * 0.04;
        targetY += (mouseY - targetY) * 0.04;
        globe.rotation.x = 0.35 + targetY * 0.3;
        camera.position.x = targetX * 40;
        camera.lookAt(scene.position);

        renderer!.render(scene, camera);
        animationId = requestAnimationFrame(animate);
    };

    animate();

    cleanup = () => {
        cancelAnimationFrame(animationId);
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('resize', onResize);
        wireGeo.dispose();
        wireMat.dispose();
        nodeGeo.dispose();
        nodeMat.dispose();
        lineGeo.dispose();
        lineMat.dispose();
        haloGeo.dispose();
        haloMat.dispose();
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
        class="pointer-events-none absolute inset-0 h-full w-full"
    ></canvas>
</template>
