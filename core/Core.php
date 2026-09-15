<?php
class Core
{

    public function run($routes)
    {

        $url = '/';

        isset($_GET['url']) ? $url .= $_GET['url'] : '';



        if ($url !== '/') {
            $url = '/' . trim($url, '/');
        }



        $url = str_replace('//', '/', $url);

        $routerFound = false;



        foreach ($routes as $path => $controller) {
            $pattern = '#^' . preg_replace('/:[a-zA-Z]+/', '(\w+)', $path) . '$#';



            if (preg_match($pattern, $url, $matches)) {

                array_shift($matches);

                $routerFound = true;



                // ARRAY [Controller, metodo]
                $currentController = "Controllers\\" . $controller[0];
                $action = $controller[1];



                // Verifica se a classe existe
                if (class_exists($currentController)) {

                    $newController = new $currentController();



                    // Verifica se o método existe
                    if (method_exists($newController, $action)) {
                        call_user_func_array([$newController, $action], $matches);
                    } else {

                        $routerFound = false;
                    }
                } else {

                    $routerFound = false;
                }

                break;
            }
        }



        // 404
        if (!$routerFound) {

            if (class_exists('NotFoundController')) {

                $controller = new NotFoundController();
                $controller->index();
            } else {

                echo "Página não encontrada (404).";
            }
        }
    }
}
