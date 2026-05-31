import ExternalLink from "@/common/ExternalLink";
import TransitionWrapper from "@/common/TransitionWrapper";
import Card, { CardContent, CardHeader } from "@/components/Card";
import Notice from "@/components/Notice";
import { useNav } from "@/hooks/use-nav";
import PageContent from "@/layout/PageContent";
import ControlsGroup from "@/pages/settings/controls/ControlsGroup";
import { useSettingsStore } from "@/store/optionsStore";
import { useAppStore } from "@/store/store";
import { __ } from "@wordpress/i18n";

const Cache = () => {
  const { wpConfigWritable, wpContentWritable, i18n } = window.SPCDash;
  const { validPro } = useAppStore();
  const { isToggleOn, cloudflareConnected } = useSettingsStore();
  const { setActiveMenuItem } = useNav();


  const controls = [
    {
      id: 'cf_fallback_cache',
      type: 'toggle',
      label: __('Enable Disk Page cache', 'wp-cloudflare-page-cache'),
      description: <>
        {__('Dramatically improves page loading speed by storing cached pages on your server.', 'wp-cloudflare-page-cache')}
        {' '}
        <ExternalLink url="https://docs.themeisle.com/super-page-cache/how-to-enable-page-caching-for-the-first-time">
          {__('More Info', 'wp-cloudflare-page-cache')}
        </ExternalLink>
      </>,
      children: cloudflareConnected ? (
        <Notice type="info" className="mt-4" description={__('If you enable the Disk Page cache, it is strongly recommended to disable all page caching functions of other plugins.', 'wp-cloudflare-page-cache')} />
      ) : null
    },
    {
      id: 'cf_fallback_cache_excluded_cookies',
      type: 'textarea',
      label: __('Skip Caching for These Cookies', 'wp-cloudflare-page-cache'),
      description: __('Pages won\'t be cached when these cookie patterns are detected. One pattern per line. Supports regex patterns (uses preg_grep for matching).', 'wp-cloudflare-page-cache'),
    },
    {
      id: 'cf_fallback_cache_excluded_urls',
      type: 'textarea',
      label: __('Prevent the following URIs from being cached', 'wp-cloudflare-page-cache'),
      description: (
        <div>
          {__('One URI per line. You can use the * for wildcard URLs.', 'wp-cloudflare-page-cache')}
          <br />
          <div className="text-sm text-muted-foreground mt-2">
            {`${__('Example', 'wp-cloudflare-page-cache')}:`}
            <br />
            <pre>
              /my-page<br />
              /my-main-page/my-sub-page<br />
              /my-main-page*<br />
            </pre>
          </div>
        </div>
      ),
    },
    {
      id: 'cf_excluded_url_params',
      type: !validPro ? 'toggle' : 'textarea',
      label: __('Ignore marketing parameters', 'wp-cloudflare-page-cache'),
      description: __('Significantly increases cache hit rate by ignoring common marketing and tracking parameters in URLs, treating them as the same page for caching purposes.', 'wp-cloudflare-page-cache'),
      utmCampaign: 'ignore-marketing-params',
      locked: !validPro,
    },
    {
      id: 'cf_prefetch_urls_mode',
      type: 'select',
      label: __('Auto prefetch URLs', 'wp-cloudflare-page-cache'),
      description: __('Prefetch internal URLs in the background to make navigation feel instant. Choose whether to prefetch on mouse hover or as links enter the viewport.', 'wp-cloudflare-page-cache'),
      options: [
        { value: 'off',      label: __('Off', 'wp-cloudflare-page-cache') },
        { value: 'hover',    label: __('On hover', 'wp-cloudflare-page-cache') },
        { value: 'viewport', label: __('When in viewport', 'wp-cloudflare-page-cache') },
      ],
    },
    {
      id: 'show_advanced',
      type: 'toggle',
      label: __('Show advanced settings', 'wp-cloudflare-page-cache'),
      description: (
        <div>
          {__('Enable to display the Advanced Settings tab (optional, recommended only for advanced configurations).', 'wp-cloudflare-page-cache')}
          {isToggleOn('show_advanced') && (
            <TransitionWrapper from="bottom" className="inline-flex">
              {' '}
              <button
                className="text-orange-500 dark:text-orange-400 ml-2 font-medium"
                onClick={() => setActiveMenuItem('advanced')}
              >
                {__('Go to Advanced Settings', 'wp-cloudflare-page-cache')}
              </button>
            </TransitionWrapper>
          )}
        </div>
      )
    },
  ]

  return (
    <PageContent>
      <Card>
        {(!wpConfigWritable || !wpContentWritable) && (
          <CardHeader>
            <Notice type="warning">
              <ul>
                {!wpConfigWritable && <li
                  className="last:mb-0"
                  dangerouslySetInnerHTML={{ __html: i18n.wpConfigNotWritable }}
                />}
                {!wpConfigWritable && <li
                  className="last:mb-0"
                  dangerouslySetInnerHTML={{ __html: i18n.wpContentNotWritable }}
                />}
              </ul>
            </Notice>
          </CardHeader>
        )}

        <CardContent className="p-0">
          <ControlsGroup controls={controls} />
        </CardContent>

      </Card>
    </PageContent>
  )
}

export default Cache;
