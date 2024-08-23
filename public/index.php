<?php

use App\User\Domain\User;
use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Doctrine\ORM\EntityManager;
use UMA\DIC\Container;
use Firebase\JWT\JWT;

require __DIR__ . '/../vendor/autoload.php';

$GLOBALS['container'] = include "../bootstrap.php";

$app = AppFactory::create();




$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/api/v1/users', function (Request $request, Response $response, $args) {
    $header = $request->getHeader("Authorization");
    $secretKey = "vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a";
    if (empty($header)) {
        return $response->withJSON(
            'not authorized',
            401,
            JSON_UNESCAPED_UNICODE
        );
    } else {
        $bearer = trim($header[0]);
        preg_match("/Bearer\s(\S+)/", $bearer, $matches);
        $token = $matches[1];
        try {
            $decoded = JWT::decode($token, $secretKey, ['HS256']);
        } catch (\Exception $e) {
            return $response->withJSON(
                'error',
                401,
                JSON_UNESCAPED_UNICODE
            );
        }

        $res = getUsers($request);

        if (!is_array($res[0])) {
            return $response->withJSON(
                $res[0],
                $res[1],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            return $response->withJSON(
                $res,
                200,
                JSON_UNESCAPED_UNICODE
            );
        }


    }

});

function queryUsers($firsName, $lastName, $birthday) {
    $em = $GLOBALS['container']->get('Doctrine\ORM\EntityManager');
    $conn = $em->getConnection();
    $where = '';
    if ($firsName !== null) {
        $where .= ' LOWER(first_name) = LOWER("'.$firsName.'") AND ';
    }
    if ($lastName !== null) {
        $where .= ' LOWER(last_name) = LOWER("'.$lastName.'") AND ';
    }
    if ($birthday !== null) {
        $date = explode('|',$birthday);
        $start = array_key_exists(0,$date) ? $date[0] : null;
        $end = array_key_exists(1,$date) ? $date[1] : null;;
        if ($end !== null) {
            $where .= ' birthday >= "'.$start.'" AND ' . ' birthday <= "' . $end . '" AND ';
        } else {
            $where .= ' birthday >= "'.$start.'" AND ';
        }

    }

    $where = rtrim($where, " ADN ");
    $sql = 'SELECT username, first_name, last_name, birthday, email FROM user u WHERE ' . $where;

    $resultSet = $conn->executeQuery($sql);
    $resArray = $resultSet->fetchAllAssociative();

    return $resArray;
}

function getUsers($request) {
    $params = $request->getQueryParams();
    $firsName = null;
    $lastName = null;
    $birthday = null;
    if (empty($params)) {
        return ['missing query params', 422];
    } else {
        if (array_key_exists('firstName', $params)) {
            $firsName = $params['firstName'];
        }
        if (array_key_exists('lastName', $params)) {
            $lastName = $params['lastName'];
        }
        if (array_key_exists('birthday', $params)) {
            $birthday  = $params['birthday'];
        }
        $users = queryUsers($firsName, $lastName, $birthday);

        if (empty($users)) {
            return ['no users found', 404];
        }
        return $users;
    }

}

$app->post('/api/v1/login', function (Request $request, Response $response, $args) {

    $check = checkCredential($request);

    if ($check[1] === 200) {
        $secretKey = "vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a";
        $issuerClaim = "slim_test";
        $audienceClaim = "api_v1";
        $issuedatClaim = time();
        $notbeforeClaim = $issuedatClaim + 10;
        $expireClaim = $issuedatClaim + 60000; // SCADENZA IN SECONDI
        $token = array(
            "iss" => $issuerClaim,
            "aud" => $audienceClaim,
            "iat" => $issuedatClaim,
            "nbf" => $notbeforeClaim,
            "exp" => $expireClaim,
            "data" => array(
                "username" => $check[0],
            ));

        $jwt = JWT::encode($token, $secretKey);

             return $response->withJSON(
                 [
                     "res" => "ok",
                     "message" => "Login ",
                     "jwt" => $jwt,
                     "username" => $check[0],
                     "expireAt" => $expireClaim
                 ],
                 $check[1],
                 JSON_UNESCAPED_UNICODE
             );
    } else {
        return $response->withJSON(
            $check[0],
            $check[1],
            JSON_UNESCAPED_UNICODE
        );
    }


});

function checkCredential(Request $request): bool | array {
    $body = $request->getParsedBody();
    $username = $body['username'];
    $password = $body['password'];

    $em = $GLOBALS['container']->get('Doctrine\ORM\EntityManager');
    $conn = $em->getConnection();
    $sql = 'SELECT username, password FROM user u WHERE username = :username';
    $resultSet = $conn->executeQuery($sql,['username' => $username]);
    $resArray = $resultSet->fetchAllAssociative();
    if (empty($resArray)) {
        return ['username or password error', 401];
    } else {
        if (password_verify($password, $resArray[0]['password'])) {
            return [$username , 200];
        } else {
            return ['username or password error', 401];
        }
    }

    return $resultSet->fetchAllAssociative();
}

$app->run();