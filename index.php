<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <title>Inicio de Sesión - Zapatería GLASÉ</title>
</head>
<body>
    <h1>Bienvenido al Sistema de Gestion De La Zapateria Glase</h1>
   
    <form action="iniciodesesion.php" method="POST" >
        <h1>Iniciar Sesion</h1>
        <hr>
        <?php 
            if (isset($_GET['error'])) { 
            ?>
            <p class="error">
            <?php 
            echo $_GET['error']; 
            ?>
            
            </p>
            <?php
        }   
        ?>
    
        <hr>
        <i class="fa-solid fa-user"></i>
        <label>Usuario</label>
        <input type="text" name="Usuario" placeholder="Ingrese su usuario">

        <i class="fa-solid fa-lock"></i>
        <label>Contraseña</label>
        <input type="password" name="Contraseña" placeholder="Ingrese su contraseña">

        <i class="fa-solid fa-right-to-bracket"></i>
        <label>Rol</label>
        <select name="Rol">
            <option value="Administrador">Administrador</option>
            <option value="Empleado">Empleado</option>
        </select>
    
      
    



        <button type="submit" class="btn btn-primary">Iniciar Sesion</button>
    
    </form>
        
</body>
</html>