<?php
/**
 * Setup wizard template.
 *
 * @package hestia
 */

$skip_wizard            = add_query_arg(
	array(
		'action' => 'hestia_dismiss_wizard',
		'nonce'  => wp_create_nonce( 'hestia_dismiss_wizard' ),
	),
	admin_url( 'admin.php' )
);
$wp_optimole_active     = is_plugin_active( 'optimole-wp/optimole-wp.php' );
$wp_orbit_fox_active    = is_plugin_active( 'themeisle-companion/themeisle-companion.php' );
$wp_otter_blocks_active = is_plugin_active( 'otter-blocks/otter-blocks.php' );
?>
<div class="hestia-wizard-wrap">
	<div class="hestia-header">
		<div class="hestia-logo">
			<div class="hestia-logo-icon">
				<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/hestia-logo.svg">
			</div>
		</div>
		<div class="hestia-dashboard-link hidden">
			<a href="<?php echo esc_url( $skip_wizard ); ?>">
				<span class="dashicons dashicons-external"></span>
			</a>
		</div>
	</div>
	<div class="hestia-wizard">
		<div id="hestiawizard" class="sw">
			<ul class="nav" style="display: none;">
				<li class="nav-item">
					<a class="nav-link" href="#step-1">
						1
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#step-2">
						2
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#step-3">
						3
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#step-4">
						4
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="#step-5">
						5
					</a>
				</li>
			</ul>
			<div class="tab-content">
				<div id="step-1" class="tab-pane" role="tabpanel" aria-labelledby="step-1">
					<div class="hestia-wizard__content">
						<div class="hestia-wizard__body welcome-step">
							<div class="hestia-card-box">
								<div class="welcom-card">
									<div class="logo">
										<a href="javascript:;">
											<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/hestia-logo.svg">
											<span><?php echo esc_html( 'Hestia' ); ?></span>
										</a>
									</div>
									<p><?php esc_html_e( 'If you are new to the Hestia theme, don\'t worry! The Hestia Setup wizard can help you get all of the essential settings set up in just 4 minutes.', 'hestia' ); ?></p>
									<div class="cta">
										<button class="hestia-btn btn-primary next-wizard">
											<?php esc_html_e( 'Start Setup Now', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
										</button>
										<div>
											<a href="<?php echo esc_url( $skip_wizard ); ?>" class="hestia-btn btn-link"><?php esc_html_e( 'Skip Setup', 'hestia' ); ?></a>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="hestia-wizard__footer"></div>
					</div>
				</div>
				<div id="step-2" class="tab-pane hestia-hide-skip-btn" role="tabpanel" aria-labelledby="step-2">
					<div class="hestia-wizard__content">
						<div class="hestia-wizard__body process-step">
							<div class="hestia-card-box">
								<div class="title-wrap">
									<h2 class="h2"><?php esc_html_e( 'Homepage Settings', 'hestia' ); ?></h2>
									<p class="p"><?php esc_html_e( 'You can choose what’s displayed on the homepage of your site. It can be posts in reverse chronological order (classic blog), or a fixed/static page.', 'hestia' ); ?></p>
								</div>
								<?php
								$show_on_front = get_option( 'show_on_front', 'posts' );
								$page_on_front = get_option( 'page_on_front', 0 );
								?>
								<div class="hestia-error-notice notice notice-error p-8 mb-20 hidden"></div>
								<div class="hestia-form-wrap site-title-form">
									<div class="form-group">
										<label class="h4 form-label pb-16"><?php esc_html_e( 'Display Settings', 'hestia' ); ?></label>
										<div class="pl-16">
											<div class="hestia-radio pb-16">
												<input type="radio" name="wizard[show_on_front]" value="posts" id="option-1" class="hestia-radio-btn">
												<label for="option-1"><?php esc_html_e( 'Your latest posts', 'hestia' ); ?></label>
											</div>
											<div class="hestia-radio pb-32">
												<input type="radio" name="wizard[show_on_front]" value="page" id="option-2" class="hestia-radio-btn" <?php checked( true ); ?>>
												<label for="option-2"><?php esc_html_e( 'Create a page called Home and set as homepage', 'hestia' ); ?></label>
											</div>
										</div>
									</div>
									<div class="form-footer">
										<button class="hestia-btn btn-primary" data-action="hestia_homepage_setting">
											<?php esc_html_e( 'Save And Continue', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
										</button>
										<span class="spinner"></span>
									</div>
								</div>
							</div>
						</div>
						<div class="hestia-wizard__footer">
							<div class="left">
								<button class="hestia-btn btn-flate prev-wizard">
									<span class="dashicons dashicons-arrow-left-alt icon-left"></span> <?php esc_html_e( 'Back', 'hestia' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
				<?php if ( ! $wp_optimole_active || ! $wp_orbit_fox_active || ! $wp_otter_blocks_active ) : ?>
					<div id="step-3" class="tab-pane" role="tabpanel" aria-labelledby="step-3">
						<div class="hestia-wizard__content">
							<div class="hestia-wizard__body process-step">
								<div class="hestia-card-box">
									<div class="title-wrap">
										<h2 class="h2"><?php esc_html_e( 'Install Our Trusted Recommendations', 'hestia' ); ?></h2>
										<p class="p"><?php esc_html_e( 'Don\'t worry, you can remove it anytime, we\'re confident that you\'ll never do', 'hestia' ); ?></p>
									</div>
									<div class="hestia-error-notice notice notice-error p-8 mb-20 hidden"></div>
									<div class="hestia-form-wrap recommendations-wrap">
										<div class="hestia-accordion pb-32">
											<?php if ( ! $wp_optimole_active ) : ?>
												<div class="hestia-accordion-item hestia-features-accordion mb-0">
													<div class="hestia-accordion-item__title hestia-accordion-checkbox__title">
														<div class="hestia-checkbox">
															<input type="checkbox" name="wizard[install_plugin][]" value="optimole-wp" class="hestia-checkbox-btn" checked>
														</div>
														<button type="button" class="hestia-accordion-item__button">
															<div class="hestia-accordion__step-title h4 pb-4"><?php esc_html_e( 'Enable performance features for your website', 'hestia' ); ?></div>
															<p class="help-text"><?php esc_html_e( 'Optimise and speed up your site with our trusted add on - It’s Free', 'hestia' ); ?></p>
															<div class="hestia-accordion__icon"><span class="dashicons dashicons-arrow-down-alt2"></span>
															</div>
														</button>
													</div>
													<div class="hestia-accordion-item__content">
														<div class="hestia-features-list">
															<ul>
																<li>
																	<div class="icon">
																		<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/optimole-logo.svg" alt="">
																	</div>
																	<div class="txt">
																		<div class="h4 pb-4"><?php esc_html_e( 'Boost your website speed', 'hestia' ); ?></div>
																		<p class="help-text"><?php esc_html_e( 'Improve your website speed and images by 80% with', 'hestia' ); ?> <a href="https://wordpress.org/plugins/optimole-wp/" target="_blank">Optimole</a></p>
																	</div>
																</li>
															</ul>
														</div>
													</div>
												</div>
											<?php endif; ?>
											<?php if ( ! $wp_orbit_fox_active || ! $wp_otter_blocks_active ) : ?>
												<div class="hestia-accordion-item hestia-features-accordion mb-0">
													<div class="hestia-accordion-item__title hestia-accordion-checkbox__title">
														<div class="hestia-checkbox">
															<?php
															$plugins = array();

															if ( ! $wp_orbit_fox_active ) {
																$plugins[] = 'themeisle-companion';
															}

															if ( ! $wp_otter_blocks_active ) {
																$plugins[] = 'otter-blocks';
															}
															?>
															<input type="checkbox" class="hestia-checkbox-btn" name="wizard[install_plugin][]" value="<?php echo esc_attr( join( '|', $plugins ) ); ?>" checked>
														</div>
														<button type="button" class="hestia-accordion-item__button">
															<div class="hestia-accordion__step-title h4 pb-4"><?php esc_html_e( 'Add more options available for Hestia Theme', 'hestia' ); ?></div>
															<p class="help-text"><?php esc_html_e( 'Take full advantage of the options this theme has to offer - It’s Free', 'hestia' ); ?></p>
															<div class="hestia-accordion__icon"><span class="dashicons dashicons-arrow-down-alt2"></span>
															</div>
														</button>
													</div>
													<div class="hestia-accordion-item__content">
														<div class="hestia-features-list">
															<ul>
																<?php if ( ! $wp_orbit_fox_active ) : ?>
																	<li>
																		<div class="icon">
																			<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/orbit-fox-logo.svg" alt="">
																		</div>
																		<div class="txt">
																			<div class="h4 pb-4"><?php esc_html_e( 'Extend Hestia Theme functionality', 'hestia' ); ?></div>
																			<p class="help-text"><?php esc_html_e( 'Social Media Buttons & Icons, Custom Menu Icons, Scripts and more with', 'hestia' ); ?> <a href="https://wordpress.org/plugins/themeisle-companion/" target="_blank">OrbitFox</a></p>
																		</div>
																	</li>
																<?php endif; ?>
																<?php if ( ! $wp_otter_blocks_active ) : ?>
																	<li>
																		<div class="icon">
																			<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/otter-blocks-logo.svg" alt="">
																		</div>
																		<div class="txt">
																			<div class="h4 pb-4"><?php esc_html_e( 'Build your website', 'hestia' ); ?></div>
																			<p class="help-text"><?php esc_html_e( 'Quickly create pages with blocks, ready-to-import designs, and advanced editor extensions.', 'hestia' ); ?> <a href="https://wordpress.org/plugins/otter_blocks/" target="_blank">Otter Blocks</a></p>
																		</div>
																	</li>
																<?php endif; ?>
															</ul>
														</div>
													</div>
												</div>
											<?php endif; ?>
										</div>
										<div class="form-footer">
											<button class="hestia-btn btn-primary" data-action="hestia_install_plugins">
												<?php esc_html_e( 'Install Now', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
											</button>
											<span class="spinner"></span>
										</div>
									</div>
								</div>
							</div>
							<div class="hestia-wizard__footer">
								<div class="left">
									<button class="hestia-btn btn-flate prev-wizard">
										<span class="dashicons dashicons-arrow-left-alt icon-left"></span> <?php esc_html_e( 'Back', 'hestia' ); ?>
									</button>
								</div>
								<div class="right">
									<button class="hestia-btn btn-flate next-wizard">
										<?php esc_html_e( 'Skip', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
									</button>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
				<div id="step-4" class="tab-pane" role="tabpanel" aria-labelledby="step-4">
					<div class="hestia-wizard__content">
						<div class="hestia-wizard__body process-step">
							<div class="hestia-card-box">
								<div class="title-wrap">
									<h2 class="h2"><?php echo wp_kses( __( 'Updates, Tutorials, Special Offers<br> and more', 'hestia' ), array( 'br' => true ) ); ?></h2>
									<p class="p"><?php echo wp_kses( __( 'Let us know your email so that we can send you product updates, helpful<br> tutorials, exclusive offers and more useful stuff.', 'hestia' ), array( 'br' => true ) ); ?></p>
								</div>
								<div class="hestia-error-notice notice notice-error p-8 mb-20 hidden"></div>
								<div class="hestia-form-wrap site-title-form">
									<div class="form-group">
										<?php $admin_email = get_bloginfo( 'admin_email' ); ?>
										<input type="text" class="form-control" name="wizard[email]" placeholder="<?php echo esc_attr( $admin_email ); ?>" value="<?php echo esc_attr( $admin_email ); ?>">
									</div>
									<div class="form-footer pt-32">
										<button class="hestia-btn btn-primary" data-action="hestia_newsletter_subscribe">
											<?php esc_html_e( 'Send Me Access', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
										</button>
										<button class="hestia-btn btn-link next-wizard" style="margin-left: 20px;">
											<?php esc_html_e( 'Skip', 'hestia' ); ?>
										</button>
										<span class="spinner"></span>
									</div>
								</div>
							</div>
						</div>
						<div class="hestia-wizard__footer">
							<div class="left">
								<button class="hestia-btn btn-flate prev-wizard">
									<span class="dashicons dashicons-arrow-left-alt icon-left"></span> <?php esc_html_e( 'Back', 'hestia' ); ?>
								</button>
							</div>
							<div class="right">
								<button class="hestia-btn btn-flate next-wizard">
									<?php esc_html_e( 'Skip', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
								</button>
							</div>
						</div>
					</div>
				</div>
				<div id="step-5" class="tab-pane" role="tabpanel" aria-labelledby="step-5">
					<div class="hestia-wizard__content">
						<div class="hestia-wizard__body process-step">
							<div class="hestia-card-box">
								<div class="finish-box">
									<div class="title-wrap">
										<h2 class="h2"><?php esc_html_e( 'Awesome! You\'ve made it to the finish line.', 'hestia' ); ?></h2>
										<p class="p"><?php esc_html_e( 'Go Go Go! Start Building your Awesome Websites With Hestia!', 'hestia' ); ?></p>
									</div>
									<div class="video-box">
										<?php
										$youtube_video = 'https://www.youtube-nocookie.com/embed/bpom4SSyo-8';
										if ( defined( 'ELEMENTOR_PATH' ) && class_exists( 'Elementor\Widget_Base', false ) ) {
											$youtube_video = 'https://www.youtube.com/embed/JOKgkykzvlg';
										}
										?>
										<iframe src="<?php echo esc_url( $youtube_video ); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
										<p class="p">
											<?php
											echo wp_kses(
												// translators: %s to document URL.
												sprintf( __( 'Need more help? Read our <a href="%s" target="_blank">documentation</a>', '', 'hestia' ), esc_url( 'https://docs.themeisle.com/article/753-hestia-doc' ) ),
												array(
													'a' => array(
														'href'   => true,
														'target' => true,
														'class'  => true,
													),
												)
											);
											?>
										</p>
									</div>
									<div class="cta">
										<a href="<?php echo esc_url( add_query_arg( array( 'return' => admin_url() ), admin_url( 'customize.php' ) ) ); ?>" class="hestia-btn btn-primary hestia-finish-btn">
											<?php esc_html_e( 'Finish Setup', 'hestia' ); ?> <span class="dashicons dashicons-arrow-right-alt icon-right"></span>
										</a>
									</div>
								</div>
							</div>
						</div>
						<div class="hestia-wizard__footer">
							<div class="left">
								<button class="hestia-btn btn-flate prev-wizard">
									<span class="dashicons dashicons-arrow-left-alt icon-left"></span> <?php esc_html_e( 'Back', 'hestia' ); ?>
								</button>
							</div>
						</div>
						<div class="gif-animation">
							<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/finish-animation.gif" alt="">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
