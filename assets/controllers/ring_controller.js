import { Controller } from '@hotwired/stimulus';
import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';

// ----------------------------------------------------------------------------
// Réglages
// ----------------------------------------------------------------------------
const RING_RADIUS = 2.20;    // rayon moyen (milieu de l'épaisseur du jonc)
const HALF_WIDTH = 0.40;    // demi-largeur du bandeau, le long de l'axe
const OUT_DEPTH = 0.24;    // saillie de la face extérieure au-delà du rayon moyen
const IN_DEPTH = 0.20;    // creux de la paroi intérieure en deçà du rayon moyen
const N_OUT = 2.0;     // exposant côté extérieur : 2 = ellipse pure
const N_IN = 5.0;     // exposant côté intérieur : plus grand = plus plat

const RING_SEGMENTS = 512;  // subdivisions autour de l'anneau
const SECTION_SEGMENTS = 128;  // subdivisions autour de la section

const FONT_FAMILY = 'Tengwar Cursive';

// Ces polices font correspondre les tengwar aux touches latines : ce que vous
// tapez ici n'est pas ce qui se lit, il faut passer par un transcripteur.
const TEXT_OUTER = 'ambar celeb ar menel silme';
const TEXT_INNER = 'lome ar anar telpe nuquerna';
const BAND_HEIGHT = 0.26;    // hauteur de la gravure, en fraction du périmètre
const TEXT_GAP = 1.2;     // écart entre deux répétitions, en multiples de la taille du texte

const GLYPH_COUNT = 40;      // repli procédural : nombre de glyphes
const GLYPH_SCALE = 0.24;    // repli procédural : taille, en fraction du pas

const SPIN_SPEED = 0.25;    // rotation sur son propre axe (rad/s)
const TILT = 0.30;    // inclinaison du haut de l'axe vers la caméra (rad)
const TILT_WOBBLE = 0.03;    // amplitude du flottement de cette inclinaison
const GLOW_BASE = 1.9;     // intensité moyenne de la gravure
const GLOW_FLICKER = 0.35;    // amplitude de la fluctuation (0 = parfaitement stable)

const ENV_BLUR = 0.15;    // flou de l'environnement réfléchi
const ENV_INTENSITY = 0.90;    // force des reflets d'environnement sur le métal

// ----------------------------------------------------------------------------
// Profil de la section
//
// Superellipse |x/a|^n + |y/b|^n = 1, dont l'exposant n et la profondeur a
// varient continûment entre la face intérieure et la face extérieure.
// n = 2 donne une ellipse, n grand donne une paroi droite à angles arrondis.
// ----------------------------------------------------------------------------
function sectionPoint(t, target = new THREE.Vector2()) {
    const a = t + Math.PI;              // t=0 → face intérieure, t=π → face extérieure
    const c = Math.cos(a);
    const s = Math.sin(a);

    const w = (c + 1) / 2;              // 0 à l'intérieur, 1 à l'extérieur
    const k = w * w * (3 - 2 * w);      // lissage, pour éviter une arête au raccord
    const depth = IN_DEPTH + (OUT_DEPTH - IN_DEPTH) * k;
    const e = 2 / (N_IN + (N_OUT - N_IN) * k);

    return target.set(
        depth * Math.sign(c) * Math.pow(Math.abs(c), e),
        HALF_WIDTH * Math.sign(s) * Math.pow(Math.abs(s), e)
    );
}

// ----------------------------------------------------------------------------
// Géométrie : révolution du profil autour de l'axe Z
// ----------------------------------------------------------------------------
function buildRingGeometry() {
    const pts = [], nrm = [], arc = [0];
    const h = 1e-4, p1 = new THREE.Vector2(), p2 = new THREE.Vector2();

    for (let j = 0; j <= SECTION_SEGMENTS; j++) {
        const t = j / SECTION_SEGMENTS * Math.PI * 2;
        pts.push(sectionPoint(t));

        // Normale 2D par différence centrée : perpendiculaire à la tangente,
        // orientée vers l'extérieur du profil.
        sectionPoint(t - h, p1);
        sectionPoint(t + h, p2);
        nrm.push(new THREE.Vector2(p2.y - p1.y, p1.x - p2.x).normalize());

        if (j > 0) arc.push(arc[j - 1] + pts[j].distanceTo(pts[j - 1]));
    }

    const perimeter = arc[SECTION_SEGMENTS];

    const positions = [], normals = [], uvs = [], indices = [];

    for (let j = 0; j <= SECTION_SEGMENTS; j++) {
        const p = pts[j], n = nrm[j];
        const v = arc[j] / perimeter;     // V proportionnel à la longueur d'arc réelle,
                                           // sinon la gravure se comprime dans les courbes
        for (let i = 0; i <= RING_SEGMENTS; i++) {
            const u = i / RING_SEGMENTS * Math.PI * 2;
            const cu = Math.cos(u), su = Math.sin(u);
            const r = RING_RADIUS + p.x;

            positions.push(r * cu, r * su, p.y);
            normals.push(n.x * cu, n.x * su, n.y);
            uvs.push(i / RING_SEGMENTS, v);
        }
    }

    for (let j = 1; j <= SECTION_SEGMENTS; j++) {
        for (let i = 1; i <= RING_SEGMENTS; i++) {
            const a = (RING_SEGMENTS + 1) * j + i - 1;
            const b = (RING_SEGMENTS + 1) * (j - 1) + i - 1;
            const c = (RING_SEGMENTS + 1) * (j - 1) + i;
            const d = (RING_SEGMENTS + 1) * j + i;
            indices.push(a, b, d, b, c, d);
        }
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setIndex(indices);
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setAttribute('normal', new THREE.Float32BufferAttribute(normals, 3));
    geometry.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));

    return { geometry, perimeter };
}

// ----------------------------------------------------------------------------
// Bande de texte tengwar
// ----------------------------------------------------------------------------
function textMetrics(ctx, text, size) {
    ctx.font = `${size}px "${FONT_FAMILY}"`;
    const m = ctx.measureText(text);
    const ascent = m.actualBoundingBoxAscent ?? size * 0.8;
    const descent = m.actualBoundingBoxDescent ?? size * 0.2;
    return { width: m.width, ascent, descent, height: ascent + descent };
}

function drawTextBand(ctx, W, H, yCenter, text) {
    // On mesure à une taille de référence, puis on met à l'échelle : la bande
    // occupe toujours la même fraction du périmètre, quelles que soient les
    // métriques de la police.
    const ref = textMetrics(ctx, text, 100);
    const size = ref.height > 0 ? 100 * (H * BAND_HEIGHT) / ref.height : H * BAND_HEIGHT;
    const m = textMetrics(ctx, text, size);

    // Le motif répété, c'est le texte PLUS un écart : sans lui la fin d'une
    // répétition touche le début de la suivante.
    const unit = m.width + size * TEXT_GAP;

    // Un nombre entier de répétitions, puis un léger étirement horizontal pour
    // tomber pile sur la largeur : le raccord est invisible au tour complet.
    const reps = Math.max(1, Math.round(W / unit));
    const sx = W / (reps * unit);

    ctx.save();
    ctx.fillStyle = '#fff';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.translate(0, yCenter - (m.descent - m.ascent) / 2);
    ctx.scale(sx, 1);
    for (let k = 0; k < reps; k++) ctx.fillText(text, k * unit, 0);
    ctx.restore();
}

// ----------------------------------------------------------------------------
// Repli : glyphes dessinés en code
// ----------------------------------------------------------------------------
function drawGlyph(ctx, rnd, s) {
    const stem = rnd();
    ctx.beginPath();
    if (stem < 0.45) { ctx.moveTo(0, -s * 2.2); ctx.lineTo(0, s * 0.9); }
    else if (stem < 0.8) { ctx.moveTo(0, -s * 0.9); ctx.lineTo(0, s * 2.2); }
    else { ctx.moveTo(0, -s * 0.9); ctx.lineTo(0, s * 0.9); }
    ctx.stroke();

    const left = rnd() < 0.5;
    const bows = rnd() < 0.3 ? 2 : 1;
    for (let b = 0; b < bows; b++) {
        const r = s * (0.60 + b * 0.36);
        ctx.beginPath();
        ctx.arc(0, 0, r, -Math.PI / 2, Math.PI / 2, left);
        ctx.stroke();
    }

    const marks = Math.floor(rnd() * 3);
    for (let m = 0; m < marks; m++) {
        const y = -s * (2.9 + m * 0.75);
        const kind = rnd();
        ctx.beginPath();
        if (kind < 0.38) {
            ctx.moveTo(-s * 0.38, y);
            ctx.lineTo(s * 0.38, y - s * 0.32);
        } else if (kind < 0.68) {
            ctx.arc(0, y, s * 0.20, 0, Math.PI * 2);
        } else {
            ctx.moveTo(-s * 0.42, y);
            ctx.quadraticCurveTo(0, y - s * 0.66, s * 0.42, y);
        }
        ctx.stroke();
    }
}

function drawProceduralBand(ctx, W, yCenter, seed) {
    const pitch = W / GLYPH_COUNT;   // W divisible par le pas : pas de raccord visible
    const s = pitch * GLYPH_SCALE;

    ctx.save();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = Math.max(4, s * 0.32);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    let state = seed;
    const rnd = () => (state = (state * 1664525 + 1013904223) % 4294967296) / 4294967296;

    for (let i = 0; i < GLYPH_COUNT; i++) {
        ctx.save();
        ctx.translate((i + 0.5) * pitch, yCenter + s * 0.55);
        drawGlyph(ctx, rnd, s);
        ctx.restore();
    }
    ctx.restore();
}

// ----------------------------------------------------------------------------
// Texture des gravures
// ----------------------------------------------------------------------------
function makeGlyphCanvas(perimeter, fontReady) {
    const W = 4096;

    // La hauteur du canvas suit le rapport (périmètre de la section) /
    // (circonférence de l'anneau), pour que les glyphes ne soient pas déformés.
    const ratio = perimeter / (2 * Math.PI * RING_RADIUS);
    const H = Math.min(1024, Math.max(128, Math.round(W * ratio / 8) * 8));

    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;

    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#000';
    ctx.fillRect(0, 0, W, H);

    const band = (yCenter, text, seed) => {
        if (fontReady) drawTextBand(ctx, W, H, yCenter, text);
        else drawProceduralBand(ctx, W, yCenter, seed);
    };

    // V=0.5 → face EXTÉRIEURE du jonc, au centre de la texture.
    band(H / 2, TEXT_OUTER, 20260911);

    // V=0 → face INTÉRIEURE, donc à cheval sur la couture haute et basse.
    // Deux passes avec le même texte et la même graine : les deux moitiés
    // se rejoignent exactement.
    band(0, TEXT_INNER, 71828182);
    band(H, TEXT_INNER, 71828182);

    return canvas;
}

function makeGlyphTexture(canvas, colorSpace) {
    const t = new THREE.CanvasTexture(canvas);
    t.wrapS = THREE.RepeatWrapping;
    t.wrapT = THREE.RepeatWrapping;
    t.colorSpace = colorSpace;
    return t;
}

// ----------------------------------------------------------------------------
// Environnement réfléchi, construit à la main
//
// Un métal ne montre que ce qui l'entoure : maîtriser les reflets, c'est
// maîtriser cette scène. Tout ce qui n'est pas une source est noir, donc
// une direction sans source ne renvoie rien.
// ----------------------------------------------------------------------------
function buildEnvironment() {
    const env = new THREE.Scene();

    // Coque sombre, vue de l'intérieur.
    env.add(new THREE.Mesh(
        new THREE.SphereGeometry(12, 32, 16),
        new THREE.MeshBasicMaterial({ color: 0x0a0806, side: THREE.BackSide })
    ));

    // Sources = disques émissifs. Des disques, donc des reflets ronds.
    // L'intensité peut dépasser 1 : le PMREM rend dans une cible flottante,
    // c'est ce qui donne des éclats francs sur l'or.
    const source = (x, y, z, radius, color, intensity) => {
        const mesh = new THREE.Mesh(
            new THREE.CircleGeometry(radius, 48),
            new THREE.MeshBasicMaterial({
                color: new THREE.Color(color).multiplyScalar(intensity)
            })
        );
        mesh.position.set(x, y, z);
        mesh.lookAt(0, 0, 0);
        env.add(mesh);
    };

    source(6, 7, 5, 5.0, 0xfff1d6, 3.0);   // source principale, en haut à droite
    source(3, -4, 7, 3.0, 0xffb877, 0.8);   // appoint chaud, devant à droite
    source(-2, 8, -6, 4.0, 0xdce6ff, 0.6);   // contre-jour froid, au-dessus derrière
    source(-8, 2, 2, 7.0, 0x6b5a44, 0.12);  // remplissage à gauche : détache la silhouette
                                             // du fond sans se lire comme un reflet

    return env;
}

// ----------------------------------------------------------------------------
// Contrôleur Stimulus : anneau 3D animé, rendu sur un <canvas>
// ----------------------------------------------------------------------------
export default class extends Controller {
    static values = {
        fontUrl: String,
    };

    connect() {
        this.disposed = false;
        this.frameId = null;
        this.resizeObserver = null;
        this.setup().catch((err) => console.error('Impossible d\'initialiser l\'anneau 3D.', err));
    }

    disconnect() {
        this.disposed = true;
        if (this.frameId !== null) cancelAnimationFrame(this.frameId);
        this.resizeObserver?.disconnect();

        this.composer?.dispose();
        this.renderer?.dispose();
        this.geometry?.dispose();
        this.material?.dispose();
        this.emissiveMap?.dispose();
        this.bumpMap?.dispose();
        this.scene?.environment?.dispose();
    }

    async setup() {
        const canvas = this.element;

        // ------------------------------------------------------------------
        // Chargement de la police, avant tout dessin
        //
        // Sans cette attente, measureText et fillText utiliseraient silencieusement
        // une police de repli et la texture serait fausse sans aucune erreur.
        // ------------------------------------------------------------------
        let fontReady = false;
        if (this.hasFontUrlValue) {
            try {
                const face = new FontFace(FONT_FAMILY, `url(${this.fontUrlValue})`);
                await face.load();
                document.fonts.add(face);
                fontReady = true;
            } catch (err) {
                console.warn(`Police « ${FONT_FAMILY} » indisponible (${this.fontUrlValue}).`,
                    'Repli sur les glyphes générés.', err);
            }
        }

        if (this.disposed) return;

        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.15;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
        camera.position.set(0, 0, 8.5);

        const pmrem = new THREE.PMREMGenerator(renderer);
        scene.environment = pmrem.fromScene(buildEnvironment(), ENV_BLUR).texture;
        pmrem.dispose();

        const key = new THREE.DirectionalLight(0xfff0d0, 2.5);
        key.position.set(4, 5, 6);
        scene.add(key);

        const { geometry, perimeter } = buildRingGeometry();

        const glyphCanvas = makeGlyphCanvas(perimeter, fontReady);
        const emissiveMap = makeGlyphTexture(glyphCanvas, THREE.SRGBColorSpace);  // carte de couleur
        const bumpMap = makeGlyphTexture(glyphCanvas, THREE.NoColorSpace);    // carte de données

        const maxAniso = renderer.capabilities.getMaxAnisotropy();
        emissiveMap.anisotropy = maxAniso;
        bumpMap.anisotropy = maxAniso;

        const material = new THREE.MeshPhysicalMaterial({
            color: 0xffc65c,
            metalness: 1.0,
            roughness: 0.14,
            envMapIntensity: ENV_INTENSITY,
            clearcoat: 0.4,
            clearcoatRoughness: 0.2,
            emissive: 0xff5a12,
            emissiveMap: emissiveMap,
            emissiveIntensity: 0,        // piloté dans la boucle
            bumpMap: bumpMap,
            bumpScale: -0.03             // négatif : les glyphes sont creusés, pas en relief
        });

        const ring = new THREE.Mesh(geometry, material);

        // L'axe de la géométrie est Z, donc pointé vers la caméra. On le bascule d'un
        // quart de tour pour le rendre vertical, puis on penche son sommet vers
        // l'observateur : l'anneau est vu presque de profil, avec un peu du dessus.
        const group = new THREE.Group();
        group.add(ring);
        group.rotation.x = -Math.PI / 2 + TILT;
        scene.add(group);

        const composer = new EffectComposer(renderer);
        composer.addPass(new RenderPass(scene, camera));

        const bloom = new UnrealBloomPass(
            new THREE.Vector2(1, 1),
            0.45,   // force
            0.08,   // rayon : très faible, pour que le flou reste localisé sur la gravure
            0.97    // seuil : haut, pour que les reflets métalliques ne le dépassent plus
        );
        composer.addPass(bloom);
        composer.addPass(new OutputPass());

        this.renderer = renderer;
        this.composer = composer;
        this.geometry = geometry;
        this.material = material;
        this.emissiveMap = emissiveMap;
        this.bumpMap = bumpMap;
        this.scene = scene;

        const resize = () => {
            const w = canvas.clientWidth;
            const h = canvas.clientHeight;
            if (w === 0 || h === 0) return;
            if (canvas.width !== Math.round(w * renderer.getPixelRatio()) ||
                canvas.height !== Math.round(h * renderer.getPixelRatio())) {
                renderer.setSize(w, h, false);
                composer.setSize(w, h);
                camera.aspect = w / h;
                camera.updateProjectionMatrix();
            }
        };

        const clock = new THREE.Clock();

        const animate = () => {
            if (this.disposed) return;

            const dt = clock.getDelta();
            const t = clock.elapsedTime;
            resize();

            ring.rotation.z += dt * SPIN_SPEED;              // rotation sur l'axe de l'anneau
            group.rotation.x = -Math.PI / 2 + TILT + Math.sin(t * 0.25) * TILT_WOBBLE;

            // Gravure allumée en permanence, avec une fluctuation obtenue en sommant
            // trois sinusoïdes de fréquences sans rapport simple : le motif ne se
            // répète pas de façon perceptible.
            const flicker = Math.sin(t * 0.73) * 0.5
                + Math.sin(t * 1.31 + 1.7) * 0.3
                + Math.sin(t * 2.57 + 4.1) * 0.2;
            material.emissiveIntensity = GLOW_BASE * (1 + GLOW_FLICKER * flicker);

            composer.render();
            this.frameId = requestAnimationFrame(animate);
        };

        animate();
    }
}
