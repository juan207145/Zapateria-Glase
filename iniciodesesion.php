<?php
    session_start();
    include 'conexion.php';
    
if (isset($_POST['Usuario']) && isset($_POST['Contraseña']) && isset($_POST['Rol'])) {
    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $Usuario = validate($_POST['Usuario']);
    $Contraseña = validate($_POST['Contraseña']);
    $Rol = validate($_POST['Rol']);
    

    if (empty($Usuario)) {
        header("Location: index.php?error=El usuario es requerido");
        exit();
    } else if (empty($Contraseña)) {
        header("Location: index.php?error=La contraseña es requerida");
        exit();
    } else  if(empty($_POST['Rol'])) {
        header("Location: index.php?error=El rol es requerido");
        exit();
    } else{

        //$Contraseña = md5($Contraseña);
        
        $sql = "SELECT * FROM iniciodesesion WHERE Usuario='$Usuario' AND Contraseña='$Contraseña' AND Rol='" . $_POST['Rol'] . "'";
        $result = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            if ($row['Usuario'] === $Usuario && $row['Contraseña'] === $Contraseña && $row['Rol'] === $_POST['Rol']) {
                $_SESSION['Usuario'] = $row['Usuario'];
                $_SESSION['Contraseña'] = $row['Contraseña'];
                $_SESSION['Rol'] = $row['Rol'];
                $_SESSION['Id'] = $row['Id'];
                header("Location: dashboard/dashboard.php");
                exit();

            } else {
                header("Location: index.php?error=Usuario o contraseña incorrectos");
                exit();
            }

        } else {
            header("Location: index.php?error=Usuario o contraseña incorrectos");
            exit();
        } 
        
    }

} else {
    header("Location: index.php");
    exit();
}