<?php
    require "vendor/autoload.php";
    $router = new \Bramus\Router\Router();
    $admin = new \Vidlii\Vidlii\Admin();

    // routes
    $router->mount("/admin", function() use($router) {
        $router->post("/", function() {
            print_r($_POST);
        });
        $router->get("/statistics", function() {
            global $admin;
            echo "<pre>"; print_r($admin->statistics((isset($_GET["advanced"]) && (int)$_GET["advanced"] == 1) ? true : false)); echo "</pre>";
        });
        $router->get("/logins", function() {
            global $admin;
            echo "<pre>"; print_r($admin->logins()); echo "</pre>";
        });
        $router->get("/", function() {
            global $admin;
            echo "administration";
        });
    });

    // general
    $router->get("/", function() {
        echo "VIDLII";
    });
    $router->get("/(.*)", function($url) {
        $publicPath = "public/$url";
        if(file_exists($publicPath)) {
            switch(strtolower(end(explode(".", $url)))) {
                case "css": $css = "text/css"; break;
                case "js": $css = "text/javascript"; break;
                default: $css = mime_content_type($publicPath); break;
            }
            header("Content-Type: $css");
            echo file_get_contents($publicPath);
        } else {
            echo "Not found";
        }
    });

    $router->run();
?>