import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';

// --- AJOUTER CES LIGNES POUR ACTIVER LES COMPOSANTS JS DE BOOTSTRAP (comme les Dropdowns) ---
import * as bootstrap from 'bootstrap';
import * as Popper from '@popperjs/core';

// Optionnel: Rendre disponibles globalement pour le débogage (bonne pratique)
window.bootstrap = bootstrap;
window.Popper = Popper;

/*
 * Welcome to your app's main JavaScript file!
 * (Le reste de votre code)
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
