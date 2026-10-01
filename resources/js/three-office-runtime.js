import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';
import { SMAAPass } from 'three/addons/postprocessing/SMAAPass.js';
import { GTAOPass } from 'three/addons/postprocessing/GTAOPass.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { RectAreaLightUniformsLib } from 'three/addons/lights/RectAreaLightUniformsLib.js';

window.THREE = THREE;

window.OFFICE_ADDONS = {
    EffectComposer,
    RenderPass,
    UnrealBloomPass,
    OutputPass,
    ShaderPass,
    SMAAPass,
    GTAOPass,
    RoomEnvironment,
    initRectAreaLights: () => RectAreaLightUniformsLib.init(),
};

document.dispatchEvent(new CustomEvent('three-office-runtime-ready'));
