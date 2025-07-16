import ViewCachedPages from "@/common/CachedPagesDrawer";
import TransitionWrapper from "@/common/TransitionWrapper";
import Card, { CardContent } from "@/components/Card";
import { formatBytes } from "@/lib/utils";

import { useSettingsStore } from "@/store/optionsStore";
import { __ } from "@wordpress/i18n";
import { Clock, Database, HardDrive, TrendingUp } from "lucide-react";

const CacheMetrics = () => {
  const { isToggleOn } = useSettingsStore();
  const { size } = window.SPCDash.metrics['cache.size'];
  const { ratio } = window.SPCDash.metrics['cache.hitmiss'];
  const { ttfb_ms } = window.SPCDash.metrics['cache.ttfb'];
  const { html_files } = window.SPCDash.metrics['cache.files'];

  const data = {
    hitRate: {
      title: __('Hit Rate', 'wp-cloudflare-page-cache'),
      value: ratio !== 'n/a' ? ratio + '%' : 'n/a',
      icon: TrendingUp,
      className: "delay-100"
    },
    cachedObjects: {
      title: __('Cached Objects', 'wp-cloudflare-page-cache'),
      value: html_files !== 'n/a' ? html_files : 'n/a',
      icon: Database,
      className: "delay-200"
    },
    cacheSize: {
      title: __('Cache Size', 'wp-cloudflare-page-cache'),
      value: size !== 'n/a' ? formatBytes(size) : 'n/a',
      icon: HardDrive,
      className: "delay-300"
    },
    ttfb: {
      title: __('Avg Response', 'wp-cloudflare-page-cache'),
      value: ttfb_ms !== 'n/a' ? ttfb_ms + 'ms' : 'n/a',
      icon: Clock,
      className: "delay-400"
    }
  }

  return (
    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      {Object.entries(data).map(([key, item]) => (
        <TransitionWrapper from="top" key={item.title} className={item.className}>
          <Card>
            <CardContent>
              <div className="flex items-center justify-between mb-2">
                <span className="text-xs font-medium text-foreground/80 uppercase tracking-wide">{item.title}</span>
                <item.icon className="w-4 h-4 text-muted-foreground" />
              </div>
              <div className="flex items-end justify-between">
                <span className="text-xl font-bold text-foreground">{item.value}</span>

                {isToggleOn('cf_purge_only_html') && key === 'cachedObjects' && (
                  <ViewCachedPages/>
                )} 
              </div>
            </CardContent>
          </Card>
        </TransitionWrapper>
      ))}
    </div>
  )
};

export default CacheMetrics;