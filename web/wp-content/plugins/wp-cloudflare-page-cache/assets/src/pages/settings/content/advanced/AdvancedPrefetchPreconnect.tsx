import Card, { CardContent, CardHeader } from "@/components/Card";
import Notice from "@/components/Notice";
import ControlsGroup from "@/pages/settings/controls/ControlsGroup";
import { __ } from "@wordpress/i18n";

const AdvancedPrefetchPreconnect = () => {
  const controls = [
    {
      id: 'dns_prefetch_domains',
      type: 'textarea',
      label: __('DNS Prefetch domains', 'wp-cloudflare-page-cache'),
      description: __('One domain per line. The browser will resolve DNS for these third-party domains in the background before they\'re needed.', 'wp-cloudflare-page-cache'),
    },
    {
      id: 'preconnect_domains',
      type: 'textarea',
      label: __('Preconnect domains', 'wp-cloudflare-page-cache'),
      description: __('One domain per line. The browser will open a connection (DNS + TCP + TLS) to these domains in advance. Use for third-party origins your site fetches from. Note: web fonts are already handled automatically by the font optimizer.', 'wp-cloudflare-page-cache'),
    },
  ];

  return (
    <Card>
      <CardHeader className="bg-muted">
        <h3 className="font-semibold text-base flex items-center">{__('Prefetch & Preconnect', 'wp-cloudflare-page-cache')}</h3>
      </CardHeader>

      <CardContent className="p-0">
        <div className="px-6 pt-6">
          <Notice
            type="warning"
            description={__('Use sparingly. Aim for at most ~4-6 preconnect and ~10 DNS prefetch domains. Too many can hurt performance.', 'wp-cloudflare-page-cache')}
          />
        </div>
        <ControlsGroup controls={controls} />
      </CardContent>
    </Card>
  );
};

export default AdvancedPrefetchPreconnect;
