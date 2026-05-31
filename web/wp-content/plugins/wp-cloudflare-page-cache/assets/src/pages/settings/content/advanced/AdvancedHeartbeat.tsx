import Card, { CardContent, CardHeader } from "@/components/Card";
import ControlsGroup from "@/pages/settings/controls/ControlsGroup";
import { __ } from "@wordpress/i18n";

const heartbeatOptions = [
  { value: 'default', label: __('Default', 'wp-cloudflare-page-cache') },
  { value: 'reduced', label: __('Reduced', 'wp-cloudflare-page-cache') },
  { value: 'disabled', label: __('Disabled', 'wp-cloudflare-page-cache') },
];

const AdvancedHeartbeat = () => {
  const controls = [
    {
      id: 'cf_heartbeat_admin',
      type: 'select',
      label: __('Heartbeat API in admin dashboard', 'wp-cloudflare-page-cache'),
      description: __('Control Heartbeat activity on wp-admin screens outside the post editor.', 'wp-cloudflare-page-cache'),
      options: heartbeatOptions,
    },
    {
      id: 'cf_heartbeat_editor',
      type: 'select',
      label: __('Heartbeat API in post editor', 'wp-cloudflare-page-cache'),
      description: __('Control autosave and locking Heartbeat requests while editing posts and pages.', 'wp-cloudflare-page-cache'),
      options: heartbeatOptions,
    },
    {
      id: 'cf_heartbeat_frontend',
      type: 'select',
      label: __('Heartbeat API on frontend', 'wp-cloudflare-page-cache'),
      description: __('Control visitor-side Heartbeat requests on the frontend of the site.', 'wp-cloudflare-page-cache'),
      options: heartbeatOptions,
    },
  ];

  return (
    <Card>
      <CardHeader className="bg-muted">
        <h3 className="font-semibold text-base flex items-center">{__('Heartbeat API Control', 'wp-cloudflare-page-cache')}</h3>
      </CardHeader>

      <CardContent className="p-0">
        <ControlsGroup controls={controls} />
      </CardContent>
    </Card>
  )
}

export default AdvancedHeartbeat;
