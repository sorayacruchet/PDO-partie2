<?php
spl_autoload_register(function ($class) {
    include '../models/' . $class . '.php';
});

// Filtrage des données potentiellement dangereuses
// htmlspecialchars() va permettre d’échapper certains caractères spéciaux comme les chevrons « < » et « > » en les transformant en entités HTML.
// trim() qui va supprimer les espaces inutiles et stripslashes() qui va supprimer les antislashes.
function cleanData($var)
{
    $var = trim($var);
    $var = stripslashes($var);
    $var = htmlspecialchars($var);
    return $var;
}


$database = new Database();
$patient = new Patients($database);

// $profilPatient["id"] = "";
// $profilPatient["lastname"] = "";
// $profilPatient["firstname"] = "";
// $profilPatient["birthdate"] = "";
// $profilPatient["mail"] = "";
// $profilPatient["phone"] = "";

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $idPatient=cleanData($_GET["idPatient"]);
    if (filter_var($idPatient, FILTER_VALIDATE_INT)) {
        $profilPatient = $patient->getPatientFileById($idPatient);
    } else {
        $arrayErrors["idPatient"] = "L'utilisateur n'existe pas.";
    }
}

// S'il y a eu soumission de formulaire, génération des fonctions de validation
// Stack tip - Checking if form has been submitted:
// For general check if there was a POST action use:
//     if (!empty($_POST))
//     This method won't work for in some cases (e.g. with check boxes and button without a name). You really should use:
//     if ($_SERVER['REQUEST_METHOD'] == 'POST')

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Génération des regex
    // Pour la saisie d'un nom ou prénom
    $regexName = "/^[a-zA-Zéèäëïçõãê -]+$/";

    // Pour la saisie d'un numéro de téléphone avec ou sans indicatif
    $regexPhone = '/^(\+[0-9]{2,3})?([0-9]){9,15}$/';

    // Pour la saisie de la date de naissance
    $regexBirthdate = "/^[1-2][0-9]{3}[-][0-1][0-9][-]([0-2][0-9]|[3][0-1])$/";

    // Initialisation du tableau d'erreurs
    $arrayErrors = [];


    // Application du premier filtre de sécurité
    $id = isset($_POST["id"]) ? cleanData($_POST["id"]) : "";
    $lastname = isset($_POST["lastname"]) ? cleanData($_POST["lastname"]) : "";
    $firstname = isset($_POST["firstname"]) ? cleanData($_POST["firstname"]) : "";
    $birthdate = isset($_POST["birthdate"]) ? cleanData($_POST["birthdate"]) : "";
    $phone = isset($_POST["phone"]) ? cleanData($_POST["phone"]) : "";
    $email = isset($_POST["email"]) ? cleanData($_POST["email"]) : "";

    // Apllication du second filtre de sécurité
    if (filter_var($id, FILTER_VALIDATE_INT)) {
        $verifiedId = $id;
    } else {
        $arrayErrors['id'] = "Identifiant invalide.";
    }

    if (preg_match($regexName, $lastname)) {
        $verifiedLastname = $lastname;
    } else {
        $arrayErrors['lastname'] = "Veuillez saisir un nom valide.";
    }

    if (preg_match($regexName, $firstname)) {
        $verifiedFirstname = $firstname;
    } else {
        $arrayErrors['firstname'] = "Veuillez saisir un prénom valide.";
    }

    if (preg_match($regexBirthdate, $birthdate)) {
        $verifiedBirthdate = $birthdate;
    } else {
        $arrayErrors['birthdate'] = "Veuillez saisir une date de naissance valide.";
    }

    if (preg_match($regexPhone, $phone)) {
        $verifiedPhone = $phone;
    } else {
        $arrayErrors['phone'] = "Veuillez saisir un numéro de téléphone valide.";
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $verifiedEmail = $email;
    } else {
        $arrayErrors['email'] = "Veuillez saisir une adresse mail valide.";
    }

    var_dump($_SERVER['REQUEST_URI'] . "?idPatient=$verifiedId");
    
    // Stockage des données saisies
    if (empty($arrayErrors)) {

        $arrayParameters = [
            "id" => $verifiedId,
            "lastname" => $verifiedLastname,
            "firstname" => $verifiedFirstname,
            "birthdate" => $verifiedBirthdate,
            "phone" => $verifiedPhone,
            "mail" => $verifiedEmail
        ];

        $br = "<br>";

        if ($patient->updatePatient($arrayParameters)) {
            $status = "✅ La demande a été traitée avec succès.";
            // Redirection vers la page du patient (cf. notion POST REDIRECT GET)
            header("Location: " . $_SERVER['REQUEST_URI'] . "?idPatient=$verifiedId", true, 303 );
        } else {
            $status = "❌ Des erreurs sont survenues pendant le traitement de la demande, veuillez recommencer.";
        }       
    } else {
        $status = "❌ Veuillez compléter tous les champs avant de poursuivre.";
    }
}
