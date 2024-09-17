<?php

namespace WebpConverter;

use WebpConverter\Action;
use WebpConverter\Conversion;
use WebpConverter\Conversion\Cron;
use WebpConverter\Conversion\Endpoint;
use WebpConverter\Error\ErrorDetectorAggregator;
use WebpConverter\Notice;
use WebpConverter\Plugin;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service;
use WebpConverter\Settings\Page;

/**
 * Class initializes all plugin actions.
 */
class WebpConverter {

	public function __construct( PluginInfo $plugin_info ) {
		$token_repository  = new TokenRepository();
		$format_factory    = new Conversion\Format\FormatFactory( $token_repository );
		$method_factory    = new Conversion\Method\MethodFactory( $token_repository, $format_factory );
		$directory_factory = new Conversion\Directory\DirectoryFactory( $format_factory );
		$plugin_data       = new PluginData( $token_repository, $method_factory, $format_factory, $directory_factory );

		( new Action\ConvertAttachmentAction( $plugin_data ) )->init_hooks();
		( new Action\ConvertPathsAction( $plugin_data, $method_factory ) )->init_hooks();
		( new Action\DeleteFileHandler() )->init_hooks();
		( new Action\DeletePathsAction( $format_factory ) )->init_hooks();
		( new Action\UploadFileHandler( $plugin_data, $token_repository, $format_factory ) )->init_hooks();
		$directory_factory->init_hooks();
		( new Endpoint\EndpointIntegrator( new Endpoint\CronConversionEndpoint( $plugin_data, $token_repository, $format_factory ) ) )->init_hooks();
		( new Endpoint\EndpointIntegrator( new Endpoint\FilesStatsEndpoint( $plugin_data, $format_factory ) ) )->init_hooks();
		( new Endpoint\EndpointIntegrator( new Endpoint\PathsEndpoint( $plugin_data, $token_repository, $format_factory ) ) )->init_hooks();
		( new Endpoint\EndpointIntegrator( new Endpoint\RegenerateEndpoint( $plugin_data, $method_factory ) ) )->init_hooks();
		( new Endpoint\EndpointIntegrator( new Endpoint\RegenerateAttachmentEndpoint() ) )->init_hooks();
		( new Conversion\ExcludedPathsOperator( $plugin_data ) )->init_hooks();
		( new Cron\CronEventGenerator( $plugin_data, $token_repository, $format_factory ) )->init_hooks();
		( new Cron\CronSchedulesGenerator() )->init_hooks();
		( new Cron\CronStatusViewer() )->init_hooks();
		( new ErrorDetectorAggregator( $plugin_info, $plugin_data, $format_factory ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\WelcomeNotice() ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\ThanksNotice() ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\CloudflareNotice() ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\TokenInactiveNotice( $plugin_data, $token_repository ) ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\BlackFridayNotice( $plugin_data ) ) )->init_hooks();
		( new Notice\NoticeIntegrator( $plugin_info, new Notice\UpgradeNotice( $plugin_data ) ) )->init_hooks();
		( new Loader\LoaderIntegrator( new Loader\HtaccessLoader( $plugin_info, $plugin_data, $format_factory ) ) )->init_hooks();
		( new Loader\LoaderIntegrator( new Loader\HtaccessBypassingLoader( $plugin_info, $plugin_data, $format_factory ) ) )->init_hooks();
		( new Loader\LoaderIntegrator( new Loader\PassthruLoader( $plugin_info, $plugin_data, $format_factory ) ) )->init_hooks();
		( new Plugin\ActivationHandler( $plugin_info, $plugin_data, $token_repository ) )->init_hooks();
		( new Plugin\DeactivationHandler( $plugin_info ) )->init_hooks();
		( new Plugin\PluginLinksGenerator( $plugin_info, $token_repository ) )->init_hooks();
		( new Plugin\UninstallHandler( $plugin_info ) )->init_hooks();
		( new Page\PageIntegrator( $plugin_info ) )
			->set_page_integration( new Page\GeneralSettingsPage( $plugin_info, $plugin_data, $token_repository, $format_factory ) )
			->set_page_integration( new Page\AdvancedSettingsPage( $plugin_info, $plugin_data, $token_repository, $format_factory ) )
			->set_page_integration( new Page\CdnSettingsPage( $plugin_info, $plugin_data, $token_repository, $format_factory ) )
			->set_page_integration( new Page\DebugPage( $plugin_info, $plugin_data ) )
			->set_page_integration( new Page\BulkOptimizationPage( $plugin_info, $plugin_data, $token_repository, $format_factory ) )
			->set_page_integration( new Page\ExpertSettingsPage( $plugin_info, $plugin_data, $token_repository, $format_factory ) )
			->init_hooks();
		( new Service\BackupExcluder( $plugin_data ) )->init_hooks();
		( new Service\CacheIntegrator( $plugin_info ) )->init_hooks();
		( new Service\CloudflareConfigurator( $plugin_info, $plugin_data ) )->init_hooks();
		( new Service\DeactivationModalLoader( $plugin_info, $plugin_data ) )->init_hooks();
		( new Service\MediaStatusViewer( $plugin_data, $token_repository, $format_factory ) )->init_hooks();
		( new Service\SiteHealthDetector( $plugin_data ) )->init_hooks();
		( new Service\RestApiUnlocker() )->init_hooks();
		( new Service\WpCliManager( $plugin_data, $token_repository, $method_factory, $format_factory ) )->init_hooks();
		( new Settings\AdminAssetsLoader( $plugin_info ) )->init_hooks();
	}
}
