<?php

namespace App\Helpers;

use \Firebase\JWT\JWT;

/**
 * Jwt is used to create / verify jwt code
 */
class AppJwt {

  public static function create($data, $expTimeInMinute = 720) {
    $tokenId = rand();
    $issuedAt = time();
    $notBefore = $issuedAt;             //Adding 10 seconds
    $expire = $notBefore * 6000;            // Adding 60 seconds
    $serverName = 'localhost'; // Retrieve the server name from config file

    /*
     * Create the token as an array
     */
    $data = [
      'iat' => $issuedAt, // Issued at: time when the token was generated
      'jti' => $tokenId, // Json Token Id: an unique identifier for the token
      'iss' => $serverName, // Issuer
      'nbf' => $notBefore, // Not before
      'exp' => $expire, // Expire
      'data' => $data
    ];

    $secretKey = JWT_SECRET;

    /*
     * Encode the array to a JWT string.
     * Second parameter is the key to encode the token.
     *
     * The output string can be validated at http://jwt.io/
     */
    return JWT::encode(
        $data, //Data to be encoded in the JWT
        $secretKey, // The signing key
        'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
    );
  }

  public static function getTokenDecode($token) {
    return JWT::decode($token, JWT_SECRET, array('HS512'));
  }

}
