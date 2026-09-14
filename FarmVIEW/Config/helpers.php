<?php


// var_dump($_SERVER['SCRIPT_NAME']);
// var_dump(dirname($_SERVER['SCRIPT_NAME']));
// die();

function accessNavigate($nivel = Null)
{
    if ($nivel == 1) {
        include('Views/templates/nav_user.php');
    } else if ($nivel == 2) {
        include('Views/templates/nav_admin.php');
    } else {
        include('Views/templates/nav.php');
    }
}

function view($viewName, $data = [])
{
    $viewPath = "Views/{$viewName}.php";

    if (file_exists($viewPath)) {
        // Extrai as variáveis do array $data para dentro da view
        extract($data);
        include $viewPath;
    } else {
        echo "Página não encontrada!";
    }
}

function base_url($path = '')
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Pega o subdiretório automaticamente (ex: /CEBIOHUB/Public)
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $protocol . '://' . $host . $base . '/' . ltrim($path, '/');
}

function msg($texto, $tipo = 'success')
{
    $alertType = "alert-{$tipo}";
    if ($tipo == 'danger') {
        $icone = '<i class="bi bi-exclamation-triangle-fill"></i>';
    } else if ($tipo == 'warning') {
        $icone = '<i class="bi bi-exclamation-circle-fill"></i>';
    } else {
        $icone = '<i class="bi bi-check-circle-fill"></i>';
    }

    return '
    <div class="alert ' . $alertType . ' alert-dismissible fade show" role="alert">
    ' . $icone . ' <strong>' . $texto . '</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}


function esc($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}


function redirectPages()
{
    if (!isset($_SESSION['usuarios_logado'])) {

        $_SESSION['msg'] = [
            'texto' => 'Faça login para acessar o sistema.',
            'color' => 'warning'
        ];

        header('Location: ' . base_url('/login'));
        exit;
    }
}
