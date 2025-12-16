import { Controller } from '@hotwired/stimulus';

// Le nom de la classe doit correspondre au nom du fichier (bonus_toggle)
export default class extends Controller {
    // Déclare les éléments HTML que le contrôleur doit pouvoir manipuler
    static targets = ["checkbox", "amountContainer", "amountInput"];

    connect() {
        // Définit l'état initial lors du chargement de la page (utile si la page est rechargée après une erreur de validation)
        this.toggle();
    }

    /**
     * Bascule la visibilité du champ de montant et vide sa valeur si caché.
     * Cette méthode est liée à l'action 'change' de la checkbox dans le Twig.
     */
    toggle() {
        const isChecked = this.checkboxTarget.checked;

        if (isChecked) {
            // Affiche le conteneur du champ de montant
            this.amountContainerTarget.style.display = 'block';
        } else {
            // Cache le conteneur du champ de montant
            this.amountContainerTarget.style.display = 'none';

            // IMPORTANT : Vide la valeur du champ réel (pour ne pas envoyer une valeur invalide si masqué)
            // On utilise this.hasAmountInputTarget pour vérifier l'existence avant d'y accéder.
            if (this.hasAmountInputTarget) {
                this.amountInputTarget.value = '';
            }
        }
    }
}
