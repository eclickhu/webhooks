<?php

/**
 *  This file is part of reflar/webhooks.
 *
 *  Copyright (c) ReFlar.
 *
 *  https://reflar.redevs.org
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 */

namespace Reflar\Webhooks\Adapters\Slack;

use Flarum\Http\UrlGenerator;
use GuzzleHttp\Exception\RequestException;
use Reflar\Webhooks\Response;

class Adapter extends \Reflar\Webhooks\Adapters\Adapter
{
    public static $client;

    protected $exception = SlackException::class;

    /**
     * Sends a message through the webhook
     * @param string $url
     * @param Response $response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws SlackException
     */
    public function send(string $url, Response $response) {
        if (!isset($response)) return;

        $res = $this->request($url, [
            "username" => $this->settings->get('reflar-webhooks.settings.discordName') ?: $this->settings->get('forum_title'),
            "avatar_url" => $this->getAvatarUrl(),
            "attachments" => [
                $this->toArray($response)
            ]
        ]);

        if ($res->getStatusCode() == 302) {
            throw new SlackException($res, $url);
        }
    }

    /**
     * @return null|string
     */
    protected function getAvatarUrl() {
        $faviconPath = $this->settings->get('favicon_path');
        $logoPath = $this->settings->get('logo_path');

        return ($faviconPath ?: $logoPath) ? app(UrlGenerator::class)->to('forum')->path('assets/' . ($faviconPath ?: $logoPath)) : null;
    }

    /**
     * @param Response $response
     * @return array
     */
    function toArray(Response $response)
    {
        $data = [
            'fallback' => $response->description . ($response->author ? " - " . $response->author->username : ""),
            'color' => $response->color,
            'title' => $response->title,
            'title_link' => $response->url,
            'text' => $response->description,
            'footer' => $this->settings->get('forum_title')
        ];

        if (isset($response->author)) {
            $data["author_name"] = $response->author->username;
            $data["author_link"] = $response->getAuthorUrl();
            $data["author_icon"] = $response->author->avatar_url;
        }

        return $data;
    }
}