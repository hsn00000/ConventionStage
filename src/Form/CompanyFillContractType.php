<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convention de Stage - Informations Entreprise</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>🏢</text></svg>">

    {# On charge les styles sans le menu du site #}
    {% block stylesheets %}
    {{ importmap('app') }}
    {% endblock %}
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            <div class="text-center mb-5">
                <h1 class="h3 fw-bold text-primary">Convention de Stage</h1>
                <p class="fs-5">Dossier de l'étudiant : <strong>{{ contract.student.firstname }} {{ contract.student.lastname|upper }}</strong></p>
                <div class="badge bg-warning text-dark px-3 py-2">Espace Entreprise</div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">

                    {{ form_start(form) }}

                    {# --- 1. ENTREPRISE --- #}
                    <h4 class="text-secondary border-bottom pb-2 mb-4">🏢 L'Organisme d'accueil</h4>

                    <div class="bg-light p-4 rounded mb-5 border">
                        <div class="mb-3">
                            {{ form_row(form.organisation.name, {label: 'Raison Sociale de l\'entreprise'}) }}
                        </div>

                        {# Si votre OrganisationType a le champ 'website' #}
                        {# {{ form_row(form.organisation.website) }} #}

                        <div class="row g-3">
                            <div class="col-md-6">{{ form_row(form.organisation.addressHq) }}</div>
                            <div class="col-md-2">{{ form_row(form.organisation.postalCodeHq) }}</div>
                            <div class="col-md-4">{{ form_row(form.organisation.cityHq) }}</div>
                        </div>

                        {# Bloc Adresse Stage si différent (optionnel) #}
                        <div class="mt-3">
                            <a class="btn btn-link btn-sm p-0 mb-2" data-bs-toggle="collapse" href="#internshipAddress" role="button">
                                Le lieu du stage est différent du siège ?
                            </a>
                            <div class="collapse" id="internshipAddress">
                                <div class="card card-body bg-white">
                                    {{ form_row(form.organisation.addressInternship) }}
                                    <div class="row">
                                        <div class="col-md-4">{{ form_row(form.organisation.postalCodeInternship) }}</div>
                                        <div class="col-md-8">{{ form_row(form.organisation.cityInternship) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">
                        <h6 class="fw-bold text-muted mb-3">Responsable Signataire</h6>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">{{ form_row(form.organisation.respName, {label: 'Nom et Prénom'}) }}</div>
                            <div class="col-md-6">{{ form_row(form.organisation.respFunction, {label: 'Fonction'}) }}</div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">{{ form_row(form.organisation.respEmail) }}</div>
                            <div class="col-md-6">{{ form_row(form.organisation.respPhone) }}</div>
                        </div>

                        <hr class="my-4 text-muted">
                        <h6 class="fw-bold text-muted mb-3">Assurance & Administratif</h6>
                        <div class="row g-3">
                            <div class="col-md-6">{{ form_row(form.organisation.insuranceName) }}</div>
                            <div class="col-md-6">{{ form_row(form.organisation.insuranceContract) }}</div>
                        </div>
                    </div>

                    {# --- 2. TUTEUR --- #}
                    <h4 class="text-secondary border-bottom pb-2 mb-4">🧑‍🏫 Le Maître de Stage (Tuteur)</h4>
                    <div class="bg-light p-4 rounded mb-5 border">
                        <div class="row g-3">
                            <div class="col-md-6">{{ form_row(form.tutor.lastname, {label: 'Nom'}) }}</div>
                            <div class="col-md-6">{{ form_row(form.tutor.firstname, {label: 'Prénom'}) }}</div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">{{ form_row(form.tutor.email) }}</div>
                            <div class="col-md-6">{{ form_row(form.tutor.telMobile, {label: 'Téléphone Portable'}) }}</div>
                        </div>
                        {# Ajoutez ici la fonction du tuteur si elle existe dans TutorType #}
                    </div>

                    {# --- 3. DÉTAILS DU STAGE --- #}
                    <h4 class="text-secondary border-bottom pb-2 mb-4">📅 Déroulement du Stage</h4>

                    <div class="mb-4">
                        {{ form_row(form.plannedActivities) }}
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-bold mb-3">Horaires de présence hebdomadaires</label>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle bg-white" id="schedule-table">
                                <thead class="table-light small">
                                <tr>
                                    <th rowspan="2" style="width: 15%;">JOUR</th>
                                    <th colspan="2">Matin</th>
                                    <th colspan="2">Après-midi</th>
                                </tr>
                                <tr>
                                    <th>Début</th><th>Fin</th><th>Début</th><th>Fin</th>
                                </tr>
                                </thead>
                                <tbody>
                                {% for day, dayForm in form.workHours %}
                                <tr>
                                    <td class="fw-bold text-uppercase small bg-light">{{ day }}</td>
                                    <td class="p-1">{{ form_widget(dayForm.m_start) }}</td>
                                    <td class="p-1">{{ form_widget(dayForm.m_end) }}</td>
                                    <td class="p-1">{{ form_widget(dayForm.am_start) }}</td>
                                    <td class="p-1">{{ form_widget(dayForm.am_end) }}</td>
                                </tr>
                                {% endfor %}
                                </tbody>
                            </table>
                        </div>
                        {% do form.workHours.setRendered() %}
                    </div>

                    {# --- 4. LOGISTIQUE --- #}
                    <h4 class="text-secondary border-bottom pb-2 mb-4">⚙️ Logistique & Gratification</h4>
                    <div class="bg-light p-4 rounded mb-4 border">
                        <div class="form-check form-switch mb-3">
                            {{ form_widget(form.deplacement) }} {{ form_label(form.deplacement) }}
                        </div>
                        <div class="form-check form-switch mb-3">
                            {{ form_widget(form.transportFreeTaken) }} {{ form_label(form.transportFreeTaken) }}
                        </div>
                        <div class="form-check form-switch mb-3">
                            {{ form_widget(form.lunchTaken) }} {{ form_label(form.lunchTaken) }}
                        </div>
                        <div class="form-check form-switch mb-3">
                            {{ form_widget(form.hostTaken) }} {{ form_label(form.hostTaken) }}
                        </div>
                        <div class="form-check form-switch mb-0">
                            {{ form_widget(form.bonus) }} {{ form_label(form.bonus) }}
                        </div>
                    </div>

                    <hr class="my-5">

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg py-3 fw-bold shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> Valider et Transmettre le dossier
                        </button>
                    </div>

                    {{ form_end(form) }}

                </div>
            </div>

            <div class="text-center mt-4 text-muted small">
                &copy; {{ "now"|date("Y") }} Lycée Gabriel Fauré - Plateforme de Convention de Stage
            </div>

        </div>
    </div>
</div>

{# SCRIPT POUR LE CALCUL DES HEURES #}
{# Copie simplifiée de celui de l'étudiant pour afficher le total si besoin #}
{% block javascripts %}
{{ importmap('app') }}
{# Note: Si vous avez besoin du script de calcul, copiez celui de new.html.twig ici #}
{% endblock %}

</body>
</html>
