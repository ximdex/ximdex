<?php
namespace Ximdex\API;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Laravel\Lumen\Application;
use Ximdex\Models\User;
use Ximdex\Runtime\App;
use Ximdex\Utils\FsUtils;


/**
 * @SWG\Swagger(
 *     schemes={"http"},
 *     basePath="/api",
 *     produces={"application/json"},
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="Ximdex CMS API",
 *         description="An API to communicate with Ximdex CMS",
 *         @SWG\Contact(email="info@ximdex.com", url="http://ximdex.com"),
 *     ),
 *     @SWG\SecurityScheme(
 *         securityDefinition="ximtoken",
 *         type="apiKey",
 *         name="ximtoken",
 *         description="Auth token",
 *         in="query"
 *     )
 * )
 */
class Manager {
    // rutas del API
    public static function addApiRoutes() {
        self::setupAuthMiddlewares();

        self::setupRoutes();
    }

    /**
     * @param $app
     */
    private static function setupAuthMiddlewares() {
        $app = Application::getInstance();

        $app->routeMiddleware( [ 'auth' => Authenticate::class, 'apicreate' => \Ximdex\API\Middleware\AuthCreateMiddleware::class, 'apiread' => \Ximdex\API\Middleware\AuthReadMiddleware::class, 'apiupdate' => \Ximdex\API\Middleware\AuthUpdateMiddleware::class, 'apidelete' => \Ximdex\API\Middleware\AuthDeleteMiddleware::class, 'apipublish' => \Ximdex\API\Middleware\AuthPublishMiddleware::class, ] );

        $app[ 'auth' ]->viaRequest( 'api', function (Request $request) {
            if ( $request->input( 'ximtoken' ) ) {
                $encryptedXimtoken = $request->input( 'ximtoken' );

                $tokenService = new \Ximdex\Services\Token();

                $ximtoken = $tokenService->decryptToken( $encryptedXimtoken, App::getValue( "ApiKey" ), App::getValue( "ApiIV" ) );

                if ( empty( $ximtoken ) ) {
                    return null;
                }

                if ( $tokenService->hasExpired( $ximtoken ) ) {
                    return null;
                }

                $username = $ximtoken[ 'user' ];
                // Is a valid user !
                $user = new User();
                $user->setByLogin( $username );
                $user_id = $user->getID();
                if ( empty( $user_id ) ) {
                    return null;
                }
                $request[ 'userID' ] = $user_id;
                $request[ 'userLogged' ] = $user->getLogin();
                return $user;
            }
            return null;
        } );
    }

    /**
     * @param $app
     */
    private static function setupRoutes() {
        $app = Application::getInstance();

        $app->get('/api/doc', function (){
            $data = FsUtils::file_get_contents(XIMDEX_ROOT_PATH . '/lib/Ximdex/API/Doc/index.html');
            return response($data);
        });

        $app->get('/api/doc.json', function (){
            $swagger = \Swagger\scan(XIMDEX_ROOT_PATH . '/lib/Ximdex/API');
            return response()->json($swagger);
        });

        /**
         *  @SWG\Get(
         *      path="/login",
         *      summary="Log in into Ximdex CMS",
         *      @SWG\Parameter(
         *          name="user",
         *          in="query",
         *          description="Username",
         *          required=true,
         *          type="string"
         *      ),
         *      @SWG\Parameter(
         *          name="pass",
         *          in="query",
         *          description="Password",
         *          required=true,
         *          type="string"
         *      ),
         *      @SWG\Response(
         *          response="200",
         *          description="Response",
         *          @SWG\Schema(
         *              @SWG\Property(
         *                  property="error",
         *                  type="integer",
         *                  format="int32"
         *              ),
         *              @SWG\Property(
         *                  property="message",
         *                  type="string"
         *              ),
         *              @SWG\Property(
         *                  property="response",
         *                  type="object",
         *                  @SWG\Property(
         *                      property="ximtoken",
         *                      type="string"
         *                  )
         *              )
         *          )
         *      ),
         *
         *  )
         */
        $app->get( 'api/login', [ 'uses' => 'Ximdex\API\Core\LoginController@index' ] );

        /**
         *  @SWG\Post(
         *      path="/login",
         *      summary="Log in into Ximdex CMS",
         *      @SWG\Parameter(
         *          name="user",
         *          in="formData",
         *          description="Username",
         *          required=true,
         *          type="string"
         *      ),
         *      @SWG\Parameter(
         *          name="pass",
         *          in="formData",
         *          description="Password",
         *          required=true,
         *          type="string"
         *      ),
         *      @SWG\Response(
         *          response="200",
         *          description="Response",
         *          @SWG\Schema(
         *              @SWG\Property(
         *                  property="error",
         *                  type="integer",
         *                  format="int32"
         *              ),
         *              @SWG\Property(
         *                  property="message",
         *                  type="string"
         *              ),
         *              @SWG\Property(
         *                  property="response",
         *                  type="object",
         *                  @SWG\Property(
         *                      property="ximtoken",
         *                      type="string"
         *                  )
         *              )
         *          )
         *      ),
         *
         *  )
         */
        $app->post( 'api/login', [ 'uses' => 'Ximdex\API\Core\LoginController@index' ] );

        $app->group( [ 'prefix' => 'api', 'middleware' => 'auth', 'namespace' => 'Ximdex\API\Core' ], function () use ($app) {

            // Language
            /**
             *  @SWG\Get(
             *      path="/lang",
             *      summary="Get all the languages of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="langid",
             *          in="query",
             *          description="if present, the info of this specific language will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdLanguage",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="IsoName",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'lang', [ 'middleware' => [ 'apiread' ], 'uses' => 'LanguageController@index' ] );
            /**
             *  @SWG\Post(
             *      path="/lang",
             *      summary="Get all the languages of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="langid",
             *          in="formData",
             *          description="if present, the info of this specific language will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdLanguage",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),@SWG\Property(
             *                          property="IsoName",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'lang', [ 'middleware' => [ 'apiread' ], 'uses' => 'LanguageController@index' ] );

            // Channel
            /**
             *  @SWG\Get(
             *      path="/channel",
             *      summary="Get all the channels of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="channelid",
             *          in="query",
             *          description="If present, the info of this specific channel will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdChannel",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),@SWG\Property(
             *                          property="Description",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'channel', [ 'middleware' => [ 'apiread' ], 'uses' => 'ChannelController@index' ] );
            /**
             *  @SWG\Post(
             *      path="/channel",
             *      summary="Get all the channels of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="channelid",
             *          in="formData",
             *          description="If present, the info of this specific channel will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdChannel",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),@SWG\Property(
             *                          property="Description",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'channel', [ 'middleware' => [ 'apiread' ], 'uses' => 'ChannelController@index' ] );

            /**
             *  @SWG\Get(
             *      path="/channel/node",
             *      summary="Get the channel of an specific node of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The node ID we want to get its associated channels",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdChannel",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),@SWG\Property(
             *                          property="Description",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'channel/node', [ 'middleware' => [ 'apiread' ], 'uses' => 'ChannelController@index' ] );

            /**
             *  @SWG\Post(
             *      path="/channel/node",
             *      summary="Get the channel of an specific node of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The node ID we want to get its associated channels",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdChannel",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          type="string"
             *                      ),@SWG\Property(
             *                          property="Description",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'channel/node', [ 'middleware' => [ 'apiread' ], 'uses' => 'ChannelController@index' ] );

            // Nodetype
            /**
             *  @SWG\Get(
             *      path="/nodetype",
             *      summary="Get all nodetypes of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="idnodetype",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="description",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="mimetype",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'nodetype', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodetypeController@index' ] );

            /**
             *  @SWG\Post(
             *      path="/nodetype",
             *      summary="Get all nodetypes of Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="idnodetype",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="description",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="mimetype",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'nodetype', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodetypeController@index' ] );

            // Node
            /**
             *  @SWG\Get(
             *      path="/node",
             *      summary="Get the information about a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The node ID we want to get the info",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="nodeType",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="version",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="creationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="modificationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="path",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="parent",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="children",
             *                          type="array",
             *                          @SWG\Items(
             *                              @SWG\Property(
             *                                  property="nodeid",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="nodeType",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="name",
             *                                  type="string"
             *                              )
             *                          )
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@index' ] );

            /**
             *  @SWG\Post(
             *      path="/node",
             *      summary="Get the information about a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The node ID we want to get the info",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="nodeType",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="version",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="creationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="modificationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="path",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="parent",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="children",
             *                          type="array",
             *                          @SWG\Items(
             *                              @SWG\Property(
             *                                  property="nodeid",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="nodeType",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="name",
             *                                  type="string"
             *                              )
             *                          )
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@index' ] );

            /**
             *  @SWG\Get(
             *      path="/node/create",
             *      summary="Creating a new node on Ximdex CMS",
             *      @SWG\Parameter(
             *          name="ximtoken",
             *          in="query",
             *          description="Auth token",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="nodeType",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="version",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="creationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="modificationDate",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="path",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="parent",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="children",
             *                          type="array",
             *                          @SWG\Items(
             *                              @SWG\Property(
             *                                  property="nodeid",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="nodeType",
             *                                  type="string"
             *                              ),
             *                              @SWG\Property(
             *                                  property="name",
             *                                  type="string"
             *                              )
             *                          )
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/create', [ 'middleware' => [ 'apicreate' ], 'uses' => 'NodeController@create' ] );

            /**
             *  @SWG\Post(
             *      path="/node/create",
             *      summary="Creating a new node on Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The parent ID where we want to create the new node",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="nodetype",
             *          in="formData",
             *          description="The nodetype (only the ID) of the new node",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="name",
             *          in="formData",
             *          description="The name of the new node to create",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Response",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/create', [ 'middleware' => [ 'apicreate' ], 'uses' => 'NodeController@create' ] );

            /**
             *  @SWG\Get(
             *      path="/node/content",
             *      summary="Creating a new node on Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The node ID where we want to set the new content",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="content",
             *          in="query",
             *          description="The content we want to set. It can be urlencoded and base64-urlencoded",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String telling that the content has been updated successfully, or a response error containing the cause of the error.
            ",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/content', [ 'middleware' => [ 'apiupdate' ], 'uses' => 'NodeController@content' ] );

            /**
             *  @SWG\Post(
             *      path="/node/content",
             *      summary="Creating a new node on Ximdex CMS",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The node ID where we want to set the new content",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="content",
             *          in="formData",
             *          description="The content we want to set. It can be urlencoded and base64-urlencoded",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String telling that the content has been updated successfully, or a response error containing the cause of the error.
            ",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/content', [ 'middleware' => [ 'apiupdate' ], 'uses' => 'NodeController@content' ] );

            /**
             *  @SWG\Get(
             *      path="/node/info",
             *      summary="Get the info of a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The id of the node",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="The info of the node",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="array",
             *                      description="The info of the node",
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="parent",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="type",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="state",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="ctime",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="atime",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="desc",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="path",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="typename",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="class",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="icon",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isdir",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isfile",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isvirtual",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isfs",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="issection",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isxml",
             *                          type="string",
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/info', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@info' ] );

            /**
             *  @SWG\Post(
             *      path="/node/info",
             *      summary="Get the info of a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The id of the node",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="The info of the node",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="array",
             *                      description="The info of the node",
             *                      @SWG\Property(
             *                          property="nodeid",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="parent",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="type",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="name",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="state",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="ctime",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="atime",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="desc",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="path",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="typename",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="class",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="icon",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isdir",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isfile",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isvirtual",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isfs",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="issection",
             *                          type="string",
             *                      ),
             *                      @SWG\Property(
             *                          property="isxml",
             *                          type="string",
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/info', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@info' ] );

            /**
             *  @SWG\Get(
             *      path="/node/schemas",
             *      summary="Get the associated schemas to a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="Your Ximdex project's ID where you want to get the schemas. If not present, all the schemas will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the valid schemas for the given node.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="array",
             *                      description="The name of the project which contains the schemas",
             *                      @SWG\Items(
             *                          @SWG\Property(
             *                              property="idschema",
             *                              type="string",
             *                              description="The ID of the schema template"
             *                          ),
             *                          @SWG\Property(
             *                              property="name",
             *                              type="string",
             *                              description="The RNG schema's name"
             *                          )
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/schemas', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@schemas' ] );

            /**
             *  @SWG\Post(
             *      path="/node/schemas",
             *      summary="Get the associated schemas to a specific node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="Your Ximdex project's ID where you want to get the schemas. If not present, all the schemas will be returned",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the valid schemas for the given node.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="array",
             *                      description="The name of the project which contains the schemas",
             *                      @SWG\Items(
             *                          @SWG\Property(
             *                              property="idschema",
             *                              type="string",
             *                              description="The ID of the schema template"
             *                          ),
             *                          @SWG\Property(
             *                              property="name",
             *                              type="string",
             *                              description="The RNG schema's name"
             *                          )
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/schemas', [ 'middleware' => [ 'apiread' ], 'uses' => 'NodeController@schemas' ] );

            /**
             *  @SWG\Get(
             *      path="/node/createxml",
             *      summary="Creating a new XML document",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The ID of the parent folder where to store our xml",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="channels",
             *          in="query",
             *          description="A comma-separated list containing the id of the channels enabled for the documents. If this parameter is not given then all the enabled channels in Ximdex will be enabled for the documents",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="name",
             *          in="query",
             *          description="The name of the XML document (and their language versions)",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="id_schema",
             *          in="query",
             *          description="The ID of the RNG schema used for the documents",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="languages",
             *          in="query",
             *          description="A comma-separated list containing the languages (es, en, etc). One document will be created for each language. If this parameter is not present, all the enabled languages in Ximdex will be used to create the documents",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the created documents for each language. For each language, it returns the nodeid and node name for the structured document created.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="container_nodeid",
             *                      type="string",
             *                  ),
             *                  @SWG\Property(
             *                      property="container_langs",
             *                      type="object",
             *                      description="Object containing for each language its nodeid and nodename."
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/createxml', [ 'middleware' => [ 'apicreate' ], 'uses' => 'NodeController@createxml' ] );

            /**
             *  @SWG\Post(
             *      path="/node/createxml",
             *      summary="Creating a new XML document",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The ID of the parent folder where to store our xml",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="channels",
             *          in="formData",
             *          description="A comma-separated list containing the id of the channels enabled for the documents. If this parameter is not given then all the enabled channels in Ximdex will be enabled for the documents",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="name",
             *          in="formData",
             *          description="The name of the XML document (and their language versions)",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="id_schema",
             *          in="formData",
             *          description="The ID of the RNG schema used for the documents",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="languages",
             *          in="formData",
             *          description="A comma-separated list containing the languages (es, en, etc). One document will be created for each language. If this parameter is not present, all the enabled languages in Ximdex will be used to create the documents",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the created documents for each language. For each language, it returns the nodeid and node name for the structured document created.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="container_nodeid",
             *                      type="string",
             *                  ),
             *                  @SWG\Property(
             *                      property="container_langs",
             *                      type="object",
             *                      description="Object containing for each language its nodeid and nodename."
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/createxml', [ 'middleware' => [ 'apicreate' ], 'uses' => 'NodeController@createxml' ] );

            /**
             *  @SWG\Get(
             *      path="/node/contentxml",
             *      summary="Set content into a XML document",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The ID of the xml node where to set the new info.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="content",
             *          in="query",
             *          description="The content we want  to set. It can be urlencoded and base64-urlencoded.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="validate",
             *          in="query",
             *          description="Indicates whether we want or not to validate our XML content against the RNG we have used. If not present, this query will work in a lazy mode and won't validate the content",
             *          required=false,
             *          type="boolean"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String telling that the xml content has been updated successfully, or a response error containing the cause of the error.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object"
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/contentxml', [ 'middleware' => [ 'apiupdate' ], 'uses' => 'NodeController@contentxml' ] );

            /**
             *  @SWG\Post(
             *      path="/node/contentxml",
             *      summary="Set content into a XML document",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The ID of the xml node where to set the new info.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="content",
             *          in="formData",
             *          description="The content we want  to set. It can be urlencoded and base64-urlencoded.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="validate",
             *          in="formData",
             *          description="Indicates whether we want or not to validate our XML content against the RNG we have used. If not present, this query will work in a lazy mode and won't validate the content",
             *          required=false,
             *          type="boolean"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String telling that the xml content has been updated successfully, or a response error containing the cause of the error.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object"
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/contentxml', [ 'middleware' => [ 'apiupdate' ], 'uses' => 'NodeController@contentxml' ] );

            /**
             *  @SWG\Get(
             *      path="/node/publish",
             *      summary="Publish documents on your server",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The ID of the node that we want to publish.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String indicating either the node has been put in the publication queue or the node doesn’t need to be published again.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object"
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/publish', [ 'middleware' => [ 'apipublish' ], 'uses' => 'NodeController@publish' ] );

            /**
             *  @SWG\Post(
             *      path="/node/publish",
             *      summary="Publish documents on your server",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The ID of the node that we want to publish.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="String indicating either the node has been put in the publication queue or the node doesn’t need to be published again.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object"
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/publish', [ 'middleware' => [ 'apipublish' ], 'uses' => 'NodeController@publish' ] );

            /**
             *  @SWG\Get(
             *      path="/node/delete",
             *      summary="Delete a document by name",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The id of the node that we want to delete.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Message containing the result of the deletion.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'node/delete', [ 'middleware' => [ 'apidelete' ], 'uses' => 'NodeController@delete' ] );

            /**
             *  @SWG\Post(
             *      path="/node/delete",
             *      summary="Delete a document by name",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The id of the node that we want to delete.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="Message containing the result of the deletion.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'node/delete', [ 'middleware' => [ 'apidelete' ], 'uses' => 'NodeController@delete' ] );

            // Search
            /**
             *  @SWG\Get(
             *      path="/search",
             *      summary="Search documents by name",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="name",
             *          in="query",
             *          description="The name of the node that we want to search for.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the node information for the nodes which has the given string name included in its name.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdNode",
             *                          description="The ID of the searched node.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          description="The name of the searched node.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Icon",
             *                          description="The image file that represents to this node on Ximdex CMS interface.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Children",
             *                          description="Number of children that the searched node has. Would be 0 if it hasn't any children.",
             *                          type="integer"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'search', [ 'middleware' => [ 'apiread' ], 'uses' => 'SearchController@index' ] );

            /**
             *  @SWG\Post(
             *      path="/search",
             *      summary="Search documents by name",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="name",
             *          in="formData",
             *          description="The name of the node that we want to search for.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the node information for the nodes which has the given string name included in its name.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="array",
             *                  @SWG\Items(
             *                      @SWG\Property(
             *                          property="IdNode",
             *                          description="The ID of the searched node.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Name",
             *                          description="The name of the searched node.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Icon",
             *                          description="The image file that represents to this node on Ximdex CMS interface.",
             *                          type="string"
             *                      ),
             *                      @SWG\Property(
             *                          property="Children",
             *                          description="Number of children that the searched node has. Would be 0 if it hasn't any children.",
             *                          type="integer"
             *                      )
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'search', [ 'middleware' => [ 'apiread' ], 'uses' => 'SearchController@index' ] );

            // Preview
            /**
             *  @SWG\Get(
             *      path="/preview",
             *      summary="Preview a node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The id of the node",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="channelid",
             *          in="query",
             *          description="The id of the channel",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="json",
             *          in="query",
             *          description="Json mode. Default false.",
             *          required=false,
             *          type="boolean"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="The node preview",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="object",
             *                      description="The preview of the node",
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'preview', [ 'middleware' => [ 'apiread' ], 'uses' => 'PreviewController@index' ] );

            /**
             *  @SWG\Post(
             *      path="/preview",
             *      summary="Preview a node",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The id of the node",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="channelid",
             *          in="formData",
             *          description="The id of the channel",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="json",
             *          in="formData",
             *          description="Json mode. Default false.",
             *          required=false,
             *          type="boolean"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="The node preview",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="projectName",
             *                      type="object",
             *                      description="The preview of the node",
             *                  )
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'preview', [ 'middleware' => [ 'apiread' ], 'uses' => 'PreviewController@index' ] );

            // Links
            /**
             *  @SWG\Get(
             *      path="/link/create",
             *      summary="Create a link",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="name",
             *          in="query",
             *          description="The name of the link.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="url",
             *          in="query",
             *          description="The URL of the link.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="query",
             *          description="The id of the parent node of the link that we want to create.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="description",
             *          in="query",
             *          description="The description of the link.",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the link id.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="nodeid",
             *                      description="The ID of the created link.",
             *                      type="string"
             *                  ),
             *              )
             *          )
             *      ),
             *  )
             */
            $app->get( 'link/create', [ 'middleware' => [ 'apicreate' ], 'uses' => 'LinkController@create' ] );

            /**
             *  @SWG\Post(
             *      path="/link/create",
             *      summary="Create a link",
             *      security={
             *        {"ximtoken": {}}
             *      },
             *      @SWG\Parameter(
             *          name="name",
             *          in="formData",
             *          description="The name of the link.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="url",
             *          in="formData",
             *          description="The URL of the link.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="nodeid",
             *          in="formData",
             *          description="The id of the parent node of the link that we want to create.",
             *          required=true,
             *          type="string"
             *      ),
             *      @SWG\Parameter(
             *          name="description",
             *          in="formData",
             *          description="The description of the link.",
             *          required=false,
             *          type="string"
             *      ),
             *      @SWG\Response(
             *          response="200",
             *          description="JSON object containing the link id.",
             *          @SWG\Schema(
             *              @SWG\Property(
             *                  property="error",
             *                  type="integer",
             *                  format="int32"
             *              ),
             *              @SWG\Property(
             *                  property="message",
             *                  type="string"
             *              ),
             *              @SWG\Property(
             *                  property="response",
             *                  type="object",
             *                  @SWG\Property(
             *                      property="nodeid",
             *                      description="The ID of the created link.",
             *                      type="string"
             *                  ),
             *              )
             *          )
             *      ),
             *  )
             */
            $app->post( 'link/create', [ 'middleware' => [ 'apicreate' ], 'uses' => 'LinkController@create' ] );
        } );
    }
}