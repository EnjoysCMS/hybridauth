<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Blocks;


use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Annotation\Block;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Block(
    name: 'Hybridauth',
    options: [
        'template' => [
            'value' => '../modules/hybridauth/template/blocks/hybridauth.twig',
            'name' => 'Путь до шаблона',
            'description' => 'Обязательно'
        ]
    ]
)]
final class HybridauthBlock extends AbstractBlock
{

    public function __construct(
        private readonly Environment $twig,
        private readonly HybridauthApp $hybridauthApp,
        private readonly ServerRequestInterface $request
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function view(): string
    {
        $removeQuery = function (UriInterface $uri, string|array $removedQuery) {
            parse_str($uri->getQuery(), $query);
            return http_build_query(
                array_filter((array)$query, function ($k) use ($removedQuery) {
                    return !in_array($k, (array)$removedQuery);
                }, ARRAY_FILTER_USE_KEY)
            );
        };

        return $this->twig->render(
            $this->getBlockOptions()->getValue('template'),
            [
                'blockOptions' => $this->getBlockOptions(),
                'hybridauth' => $this->hybridauthApp->getHybridauth(),
                'currentUrl' => $this->request
                    ->getUri()
                    ->withQuery(
                        $removeQuery(
                            $this->request->getUri(),
                            [HybridauthApp::ERROR_QUERY]
                        )
                    )->__toString()
            ]
        );
    }
}
