<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Blocks;


use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class HybridauthBlock extends AbstractBlock
{
    private Environment $twig;
    private string $templatePath;

    /**
     * @param ContainerInterface&FactoryInterface $container
     * @param Entity $block
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(private ContainerInterface $container, Entity $block)
    {
        parent::__construct($block);
        $this->twig = $this->container->get(Environment::class);
        $this->templatePath = (string)$this->getOption('template');
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function view(): string
    {
        /** @var ServerRequestInterface $request */
        $request = $this->container->get(ServerRequestInterface::class);

        $removeQuery = function (UriInterface $uri, string|array $removedQuery) {
            parse_str($uri->getQuery(), $query);
            return http_build_query(
                array_filter((array)$query, function ($k) use ($removedQuery) {
                    return !in_array($k, (array)$removedQuery);
                }, ARRAY_FILTER_USE_KEY)
            );
        };

        return $this->twig->render(
            $this->templatePath,
            [
                'blockOptions' => $this->getOptions(),
                'hybridauth' => $this->container->get(HybridauthApp::class)->getHybridauth(),
                'currentUrl' => $request
                    ->getUri()
                    ->withQuery(
                        $removeQuery(
                            $request->getUri(),
                            [HybridauthApp::ERROR_QUERY]
                        )
                    )->__toString()
            ]
        );
    }
}
