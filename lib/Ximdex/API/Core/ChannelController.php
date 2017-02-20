<?php
namespace Ximdex\API\Core;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ximdex\API\APIResponse;
use Ximdex\Models\Channel;
use Ximdex\Models\User;
use Ximdex\Services\Node;

class ChannelController extends Controller  {

    /**
     * <p>Default method for this action</p>
     * <p>Gets all registered channels or a specific channel</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function index( Request $request )
    {
        $response = new APIResponse;

        $channelId = $request->input("channelid", "");
        if ($channelId == null || $channelId == "") {
            $channels = $this->getChannelInfo();
        } else {
            $c = new Channel($channelId);
            if ($c->GetID() == null) {
                return $response->setStatus(APIResponse::ERROR)->setMessage("The channel ID given is not a channel.");
            }

            $channels = $this->getChannelInfo($c->GetID());
        }

        if (empty($channels)) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("No channels found");
        }

        return $response->setResponse($channels);
    }

    /**
     * <p>Gets the valid channels for the given node</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function node( Request $request )
    {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'nodeid' => 'required'] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'The nodeid parameter is missing' );
        }

        $nodeid = $request->input('nodeid');

        /* @var $user User */
        $user = $request->user();
        $username = $user->getLogin();

        $node = new Node($nodeid);

        if ($node->GetID() == null) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("The node ${nodeid} does not exist");
        }

        $nodeService = new Node();

        $hasPermissionOnNode = $nodeService->hasPermissionOnNode($username, $nodeid);

        if (!$hasPermissionOnNode) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("The user does not have permission on node ${nodeid}");
        }

        $channel = new Channel();
        $channels = $channel->getChannelsForNode($nodeid);

        if (empty($channels) || $channels == null) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('No channels found for the node');
        }

        return $response->setResponse($channels);
    }

    /**
     * <p>Gets the registered channels or a specific channel if a channel id is given</p>
     * @param int $channel The chanel id
     * @return array containing the requested channels
     */
    private function getChannelInfo($channelId = null)
    {

        $channel = new Channel();
        $channels = array();

        if ( !empty($channelId) ) {
            $channel->SetID($channelId);
            $channelItem = array(
                'IdChannel' => $channelId,
                'Name' => $channel->get('Name'),
                'Description' => $channel->get('Description')
            );
            array_push($channels, $channelItem);
        } else {
            $channelsIds = $channel->GetAllChannels();
            foreach ($channelsIds as $channelItemId) {
                $ch = new Channel($channelItemId);
                $channelItem = array(
                    'IdChannel' => $ch->get('IdChannel'),
                    'Name' => $ch->get('Name'),
                    'Description' => $ch->get('Description')
                );
                array_push($channels, $channelItem);
            }
        }
        return $channels;
    }
}