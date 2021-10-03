<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      title="BileMo REST API Documentation",
 *      description="REST API Documentation of BileMo",
 *      version="1.0"
 * )
 * @OA\Server(
 *      url="https://localhost:8000/",
 *      description="BileMo REST API"
 * )
 * @OA\SecurityScheme(
 *      securityScheme="Bearer",
 *      type="http",
 *      description="Enter JWT Bearer token",
 *      scheme="bearer",
 *      bearerFormat="JWT"
 * )
 * @OA\Tag(name="Login", description="")
 * @OA\Tag(name="Products", description="accessible by simple users owned by a customer")
 * @OA\Tag(name="Users", description="accessible by customers")
 * 
 * @OA\Schema(
 *      schema="contentResponseError",
 *      @OA\Property(property="code", type="int"),
 *      @OA\Property(property="message", type="string")
 * )
 * @OA\Schema(
 *      schema="contentResponsePropertiesErrors",
 *      @OA\Property(property="code", type="int", example=400),
 *      @OA\Property(
 *          property="errors",
 *          type="array",
 *          @OA\Items(
 *              @OA\Property(property="propertyPath", type="string"),
 *              @OA\Property(property="message", type="string")
 *          )
 *      )
 * )
 * 
 * @OA\Examples(
 *      example="AccessForbiddenExample",
 *      value= {"code":403, "message":"L'accès à cette ressource n'est pas autorisé"}
 * )
 * 
 * @OA\Response(
 *      response="ProductNotFound",
 *      description="Not Found",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":404, "message":"Ce produit n'existe pas"}
 *      )
 * )
 * @OA\Response(
 *      response="ProductBadRequest",
 *      description="Bad Request",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponsePropertiesErrors",
 *          example={"code":400, "errors":{
 *              {"propertyPath":"[order]", "message":"Valeur erronée"},
 *              {"propertyPath":"[page]", "message":"Nombre entier positif attendu - zéro exclu"}
 *          }}
 *      )
 * )
 * @OA\Response(
 *      response="UserNotFound",
 *      description="Not Found",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":404, "message":"Cet utilisateur n'existe pas"}
 *      )
 * )
 * @OA\Response(
 *      response="UserCreateBadRequest",
 *      description="Bad Request",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponsePropertiesErrors",
 *          example={"code":400, "errors":{
 *              {"propertyPath":"username", "message":
 *                  "Le nom d'utilisateur peut comporter des caractères alphanumériques, points, tirets et underscores"
 *              },
 *              {"propertyPath":"password", "message":"Le mot de passe doit être renseigné"}
 *          }}
 *      )
 * )
 * @OA\Response(
 *      response="UserCreateForbidden",
 *      description="Forbidden",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          examples={
 *              "one": {"summary":"User creation limit reached", "value":{
 *                          "code":403, 
 *                          "message":"Création d'utilisateur refusée - limite de 20 utilisateurs par client"
 *                     }},
 *              "two": @OA\Examples(example="Not connected as a customer", 
 *                          ref="#/components/examples/AccessForbiddenExample")
 *          }
 *      )
 * )
 * @OA\Response(
 *      response="UserUpdateForbidden",
 *      description="Forbidden",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          examples={
 *              "one": {"summary":"User to act on is not owned by you",
 *                      "value":{"code":403, "message":"Droit de modification de cet utilisateur refusé"}},
 *              "two": @OA\Examples(example="Not connected as a customer", 
 *                          ref="#/components/examples/AccessForbiddenExample")
 *          }
 *      )
 * )
 * @OA\Response(
 *      response="UserUpdateBadRequest",
 *      description="Bad Request",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":400, "message":"Syntax error"}
 *      )
 * )
 * @OA\Response(
 *      response="UserDeleteForbidden",
 *      description="Forbidden",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          examples = {
 *              "one": {"summary":"User to act on is not owned by you",
 *                      "value":{"code":403, "message":"Droit de suppression de cet utilisateur refusé"}},
 *              "two": @OA\Examples(example="Not connected as a customer", 
 *                          ref="#/components/examples/AccessForbiddenExample")
 *          }
 *      )
 * )
 * @OA\Response(
 *      response="LoginUnauthorized",
 *      description="Unauthorized",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":401, "message":"Informations de connexion non valides"}
 *      )
 * )
 * @OA\Response(
 *      response="LoginBadRequest",
 *      description="Bad Request",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":400, "message":"La requête ne peut être traitée correctement"}
 *      )
 * )
 * @OA\Response(
 *      response="ProductActionForbidden",
 *      description="Forbidden - Not connected as a simple user",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":403, "message":"L'accès à cette ressource n'est pas autorisé"}
 *      )
 * )
 * @OA\Response(
 *      response="UserActionForbidden",
 *      description="Forbidden - Not connected as a customer",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          example={"code":403, "message":"L'accès à cette ressource n'est pas autorisé"}
 *      )
 * )
 * @OA\Response(
 *      response="AccessUnauthorized",
 *      description="Unauthorized",
 *      @OA\JsonContent(
 *          ref="#/components/schemas/contentResponseError",
 *          examples = {
 *              "one": {"summary":"Access token is missing",
 *                      "value":{"code":401, "message":"Jeton d'identification JWT manquant"}},
 *              "two": {"summary":"Access token is not valid",
 *                      "value":{"code":401, "message":"Jeton d'identification JWT non valide"}},
 *              "three": {"summary":"Access token is expired",
 *                      "value":{"code":401, "message":"Jeton d'identification JWT expiré"}}
 *          }
 *      )
 * )
 */
