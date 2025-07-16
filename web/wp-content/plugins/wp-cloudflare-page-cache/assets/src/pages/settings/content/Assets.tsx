import ControlsGroup from "@/pages/settings/controls/ControlsGroup";
import ExternalLink from "@/common/ExternalLink";
import Notice from "@/components/Notice";
import PageContent from "@/layout/PageContent";
import Card, { CardContent, CardHeader } from "@/components/Card";
import { useSettingsStore } from "@/store/optionsStore";
import { useAppStore } from "@/store/store";
import { __ } from "@wordpress/i18n";
import Button from "@/components/Button";

const Assets = () => {
  const { i18n, homeURL } = window.SPCDash;
  const { validPro } = useAppStore();
  const { pageCacheOn, isToggleOn } = useSettingsStore();

  const controls = {
    "js": [{
      id: 'cf_defer_js',
      type: 'toggle',
      label: __('Defer Javascript', 'wp-cloudflare-page-cache'),
      description: <>
        {__('Deferring Javascript eliminates render-blocking JS on your site and can improve load time.', 'wp-cloudflare-page-cache')}
        {' '}
        <ExternalLink url="https://docs.themeisle.com/article/2058-defer-js">
          {__('More Info', 'wp-cloudflare-page-cache')}
        </ExternalLink>
      </>,
      utmCampaign: 'defer-js',
      locked: !validPro
    },
    {
      id: 'cf_delay_js',
      type: 'toggle',
      label: __('Delay Javascript', 'wp-cloudflare-page-cache'),
      description: <>
        {__('It makes the website faster by waiting to load JavaScript files until the user interacts with the page, like scrolling or clicking.', 'wp-cloudflare-page-cache')}
        {' '}
        <ExternalLink url="https://docs.themeisle.com/article/2057-delay-js">
          {__('More Info', 'wp-cloudflare-page-cache')}
        </ExternalLink>
      </>,
      utmCampaign: 'delay-js',
      locked: !validPro
    },
    {
      id: 'cf_delay_js_excluded_files',
      type: 'textarea',
      placeholder: 'example-1.min.js\nexample-2.min.js',
      label: __('Exclude JS', 'wp-cloudflare-page-cache'),
      description: __('Enter keywords (one per line) to be matched against external file sources or inline JavaScript content.', 'wp-cloudflare-page-cache'),
      utmCampaign: 'delay-js-exclusion-files',
      hide: validPro && !isToggleOn('cf_delay_js'),
      locked: !validPro
    },
    {
      id: 'cf_delay_js_excluded_paths',
      type: 'textarea',
      placeholder: '/about-us\n/blog/awesome-post',
      label: __('Exclude pages', 'wp-cloudflare-page-cache'),
      description: <span dangerouslySetInnerHTML={{ __html: i18n.excludeJsPagesDescription }} />,
      utmCampaign: 'delay-js-exclusion-paths',
      hide: validPro && !isToggleOn('cf_delay_js'),
      locked: !validPro
    },
  ],
  "fonts": [
    {
      id: 'optimize_google_fonts',
      type: 'toggle',
      label: __('Optimize Google Fonts', 'wp-cloudflare-page-cache'),
      description: __('Combine multiple fonts into a single request or CSS file.', 'wp-cloudflare-page-cache'),
    },
    {
      id: 'local_google_fonts',
      type: 'toggle',
      label: __('Local Google Fonts', 'wp-cloudflare-page-cache'),
      description: __('Download and load Google Fonts from your server instead of Google\'s servers.', 'wp-cloudflare-page-cache'),
    },
  ],
  "assets": [
    {
      id: 'enable_assets_manager',
      type: 'toggle',
      label: __('Enable assets manager', 'wp-cloudflare-page-cache'),
      description: __('Control CSS & JavaScript loading based on specific contexts.'),
      children: isToggleOn('enable_assets_manager') && (
        <Button
          className="mt-2"
          href={homeURL + '?spc_assets=yes'}
          target="_blank"
          variant="orange"
          size="sm">
          {__('Manage assets', 'wp-cloudflare-page-cache')}
        </Button>
      )
    },
  ]};

  return (
    <PageContent>
      <Card>
          {(validPro && !pageCacheOn) && (
            <CardHeader>
              <Notice type="warning">
                <span dangerouslySetInnerHTML={{ __html: i18n.warningJsSection }} />
              </Notice>
            </CardHeader>
          )}

          <CardHeader className="bg-muted">
            <h3 className="font-semibold text-base flex items-center">{__('Javascript Optimizations', 'wp-cloudflare-page-cache')}</h3>
          </CardHeader>

          <CardContent className="p-0">
            <ControlsGroup controls={controls.js} />
          </CardContent>

      </Card>
      <Card>
          <CardHeader className="bg-muted">
            <h3 className="font-semibold text-base flex items-center">{__('Fonts Optimizations', 'wp-cloudflare-page-cache')}</h3>
          </CardHeader>

            <CardContent className="p-0">
              <ControlsGroup controls={controls.fonts} /> 
            </CardContent>
      </Card>
      <Card>
        <CardHeader className="bg-muted">
          <h3 className="font-semibold text-base flex items-center">{__('Assets Manager', 'wp-cloudflare-page-cache')}</h3>
        </CardHeader>

        <CardContent className="p-0">
          <ControlsGroup controls={controls.assets} />
        </CardContent>
      </Card>
    </PageContent>
  )
}

export default Assets;
