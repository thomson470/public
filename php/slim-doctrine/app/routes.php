<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \App\com\sprint\sms\api\bean\Response as Result;
use \App\com\sprint\sms\api\bean\CodeMsg as CodeMsg;

use \App\com\sprint\sms\api\service\AccessService;

/*
 * CORS
 *
 */
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Access-Key')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});
$app->add(function($request, $response, $next) {
    $route = $request->getAttribute("route");
    $methods = [];
    if (!empty($route)) {
        $pattern = $route->getPattern();
        foreach ($this->router->getRoutes() as $route) {
            if ($pattern === $route->getPattern()) {
                $methods = array_merge_recursive($methods, $route->getMethods());
            }
        }       
    } else {
        $methods[] = $request->getMethod();
    }    
    $response = $next($request, $response);
    return $response->withHeader("Access-Control-Allow-Methods", implode(",", $methods));
});



/*
 * API
 * Format:
 * Path Index 1: untuk mengarahkan ke class Controller dengan merubah huruf depan menjadi huruf besar, cth: /role/a/b -> RoleController
 * Path Index selanjutnya akan lookup (invoke) method, dimana '/' direplace dengan '__', cth: /role/a/b -> RoleController::a__b()
 */
$app->post('/api/[{path:.*}]', function(Request $request, Response $response, $args) use ($app) {
	try {
		$path = "/" . $args["path"];
		$container = $app->getContainer();
		$logger = $container->get('logger');
		$entityManager = $container->get('em');
		$cache = $container->get('cache');
		$settings = $container["settings"];
		$accessService = new AccessService($logger, $entityManager, $cache, $settings);
		$valid = $accessService->validatePath($request, $path);
		if ($valid !== null) {
			$obj = $valid->toFormatObject();
			$response->withJson($obj);
			unset($obj);
			return $response->withHeader("Content-Type", "application/json");
		}		
		$exp = explode("/", $path);
		$ctr = $exp[1];	
		$ctr = strtoupper(substr($ctr, 0, 1)) . substr($ctr, 1);	
		$mtd = "";
		for ($i = 2; $i < count($exp); $i++) {	
			if ($i == 2) {
				$mtd = $mtd . $exp[$i];
			} else {
				$mtd = $mtd . "__" . $exp[$i];
			}
		}
		unset($exp);		
		$tgt = "\\App\\com\\sprint\\sms\\api\\controller\\" . $ctr . "Controller";
		$cls = new ReflectionClass($tgt);
		$ins = $cls->newInstanceArgs(array($app, $request));
		$ref = new ReflectionMethod($tgt, $mtd);
		$res = $ref->invoke($ins);
		if (isset($res)) {
			$obj = $res->toFormatObject();
			$response->withJson($obj);
			unset($obj);
			return $response->withHeader("Content-Type", "application/json");
		}
		return $response;
	} catch(Exception $e) {
		$app->getContainer()->get('logger')->error($e->getMessage());
		$res = Result::ERROR_CODE("99", $e->getMessage());
		$obj = $res->toFormatObject();
		$response->withJson($obj);
		unset($obj);
		return $response->withHeader("Content-Type", "application/json");
	}	
});


/*
 * PRINT ALL REQUEST OBJECT
 */
$app->map(
	['GET', 'POST', 'PUT', 'DELETE', /*'OPTIONS',*/ 'HEAD', 'TRACE', 'PATCH', 'CONNECT'],
	'/test/[{path:.*}]', 
	function(Request $request, Response $response, $args) use ($app) 
{
	$out = $response->getBody();
	
	$out->write("<<< APC Enabled >>>\n");
	$apc = extension_loaded('apc');
	$out->write($apc);
	$out->write("\n\n");
	
	$out->write("<<< request->getMethod() >>>\n");
	$str = $request->getMethod();
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getRequestTarget() >>>\n");
	$str = $request->getRequestTarget();
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getUri() >>>\n");
	$str = $request->getUri();
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getHeaders() >>>\n");
	$str = json_encode($request->getHeaders());
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getAttributes() >>>\n");
	$str = json_encode($request->getAttributes());
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getQueryParams() >>>\n");
	$str = json_encode($request->getQueryParams());
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< request->getParams() >>>\n");
	//$str = json_encode($request->getParams());
	//$out->write($str);
	//$mtds = $request->getParams();
	//foreach($mtds as $key) {
	//	$out->write("$key = " . $request->getParam("$key") . "\n");
	//}
	$params = $request->getParams();
	//$keys = array_keys(params);
	foreach($params as $key => $value) {
		$out->write("$key = $value \n");
	}
	$out->write("\n\n");
	
	$out->write("<<< request->getServerParams() >>>\n");
	$str = json_encode($request->getServerParams());
	$out->write($str);
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (request) >>>\n");
	$mtds = get_class_methods($request);
	foreach($mtds as $key) {
		$out->write("$key\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (response) >>>\n");
	$mtds = get_class_methods($response);
	foreach($mtds as $key) {
		$out->write("$key\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (application) >>>\n");
	$mtds = get_class_methods($app);
	foreach($mtds as $key) {
		$r = new ReflectionMethod("Slim\App", $key);
		$params = $r->getParameters();
		$out->write("$key = " . json_encode($params) . "\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (container) >>>\n");
	$mtds = get_class_methods($app->getContainer());
	foreach($mtds as $key) {
		$r = new ReflectionMethod("Slim\Container", $key);
		$params = $r->getParameters();
		$out->write("$key = " . json_encode($params) . "\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (logger) >>>\n");
	$mtds = get_class_methods($app->getContainer()->get('logger'));
	foreach($mtds as $key) {
		$r = new ReflectionMethod("Monolog\Logger", $key);
		$params = $r->getParameters();
		$out->write("$key = " . json_encode($params) . "\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (EntityManager) >>>\n");
	$em = $app->getContainer('settings')['em'];
	$mtds = get_class_methods($em);
	foreach($mtds as $key) {
		$r = new ReflectionMethod("Doctrine\ORM\EntityManager", $key);
		$params = $r->getParameters();
		$out->write("$key = " . json_encode($params) . "\n");
	}
	$out->write("\n\n");
	
	$out->write("<<< FUNCTION (Logger) >>>\n");
	$logger = $app->getContainer('settings')['logger'];
	$mtds = get_class_methods($logger);
	foreach($mtds as $key) {
		$r = new ReflectionMethod("Monolog\Logger", $key);
		$params = $r->getParameters();
		$out->write("$key = " . json_encode($params) . "\n");
	}
	$out->write("\n\n");
	
	return $response;
});
