<?php

function chatbotScriptPorRol($rol){
    $archivos = [
        'visitante' => 'chatbot_visitante.html',
        'anfitrion' => 'chatbot_anfitrion.html',
        'invitado'  => 'chatbot_invitado.html',
    ];

    if (!isset($archivos[$rol])) {
        return '';
    }

    $ruta = ROOT_PATH . "/views/chatbots/" . $archivos[$rol];

    return file_exists($ruta) ? file_get_contents($ruta) : '';
}

function loadTPL($name, $section){
    $buffer = file_get_contents(ROOT_PATH . "/views/" . $name . "_view.html");

    $app_vars = ["APP_NAME" => APP_NAME,
    "APP_DESCRIPTION" => APP_DESCRIPTION];

    foreach ($app_vars as $key => $value) {
        $buffer = setVarTPL($key, $value, $buffer);
    }

    $buffer = setVarTPL("APP_SECTION", $section, $buffer);

    $rol = $_SESSION['rol'] ?? 'invitado';
    $buffer = setVarTPL("CHATBOT_SCRIPT", chatbotScriptPorRol($rol), $buffer);

    return $buffer;
}

function setVarTPL($markup, $value, $stream_content){
    return str_replace("{".$markup."}", $value, $stream_content);
}

function printTPL($stream_content){
    echo $stream_content;
}

?>