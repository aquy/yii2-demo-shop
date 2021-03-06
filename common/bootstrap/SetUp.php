<?php

namespace common\bootstrap;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use frontend\urls\CategoryUrlRule;
use frontend\urls\PageUrlRule;
use shop\cart\Cart;
use shop\cart\cost\calculator\DynamicCost;
use shop\cart\cost\calculator\SimpleCost;
use shop\cart\storage\HybridStorage;
use shop\services\newsletter\MailChimp;
use shop\services\newsletter\Newsletter;
use shop\services\sms\LoggedSender;
use shop\services\sms\SmsRu;
use shop\services\sms\SmsSender;
use shop\services\yandex\ShopInfo;
use shop\services\yandex\YandexMarket;
use shop\readModels\PageReadRepository;
use shop\readModels\Shop\CategoryReadRepository;
use shop\useCases\ContactService;
use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\mail\MailerInterface;
use yii\rbac\ManagerInterface;

class SetUp implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $container = \Yii::$container;

        $container->setSingleton(Client::class, function () {
            return ClientBuilder::create()->build();
        });

        $container->setSingleton(MailerInterface::class, function () use ($app) {
            return $app->mailer;
        });

        $container->setSingleton('cache', function () use ($app) {
            return $app->cache;
        });

        $container->setSingleton('frontend-url-manager', function () use ($app) {
            return $app->get('frontendUrlManager');
        });

        $container->setSingleton(ManagerInterface::class, function () use ($app) {
            return $app->authManager;
        });

        $container->setSingleton(ContactService::class, [], [
            $app->params['adminEmail']
        ]);

        $container->set(CategoryUrlRule::class, [], [
            Instance::of(CategoryReadRepository::class),
            Instance::of('cache'),
        ]);

        $container->set(PageUrlRule::class, [], [
            Instance::of(PageReadRepository::class),
            Instance::of('cache'),
        ]);

        $container->setSingleton(Cart::class, function () use ($app) {
            return new Cart(
                new HybridStorage($app->user, 'cart', 3600 * 24, $app->db),
                new DynamicCost(new SimpleCost())
            );
        });

        $container->setSingleton(YandexMarket::class, [], [
            new ShopInfo($app->name, $app->name, $app->params['frontendHostInfo']),
        ]);

        $container->setSingleton(Newsletter::class, function () use ($app) {
            return new MailChimp(
                new \DrewM\MailChimp\MailChimp($app->params['mailChimpKey']),
                $app->params['mailChimpListId']
            );
        });

        $container->setSingleton(SmsSender::class, function () use ($app) {
            return new LoggedSender(
                new SmsRu($app->params['smsRuKey']),
                \Yii::getLogger()
            );
        });
    }
}