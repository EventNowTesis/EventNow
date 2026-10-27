<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function dbConnect(){
    static $db = null;

    if ($db === null) {
        $db = mysqli_connect(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME
        );

        if (!$db) {
            die("Error de conexión a MySQL: " . mysqli_connect_error());
        }

        mysqli_set_charset($db, "utf8mb4");
    }

    return $db;
}


function dbQuery($query){
    $db = dbConnect();

    $response = mysqli_query($db, $query);

    if (!$response) {
        die("Error SQL: " . mysqli_error($db));
    }

    return mysqli_fetch_all($response, MYSQLI_ASSOC);
}

?>