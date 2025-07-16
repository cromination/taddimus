import { spcApi } from "@/lib/api";
import { formatBytes, formatNumberToReadableFormat } from "@/lib/utils";
import { useEffect, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { ArrowUpDown, HardDrive } from "lucide-react";

const CloudflareHeaderAnalytics = () => {
  const [analytics, setAnalytics] = useState<Record<string, any> | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    setIsLoading(true);
    spcApi.getCloudflareAnalytics().then((response) => {
      setIsLoading(false);
      if (response.success && response.data?.requests && response.data?.bytes) {
        setAnalytics(response.data || null);
      }
    }).catch(() => {
      setIsLoading(false);
    });
  }, []);

  if (isLoading) {
    return <div className="flex gap-2 items-center">
      <span className="w-12 md:w-30 h-4 bg-muted-foreground/20 rounded animate-pulse" />
      <span>•</span>
      <span className="w-12 md:w-30 h-4 bg-muted-foreground/20 rounded animate-pulse" />
    </div>;
  }

  if (!analytics) {
    return null;
  }

  const { requests, bytes } = analytics;

  const requestsPerSecond = formatNumberToReadableFormat(requests / 24 / 60 / 60);

  return (
    <div className="gap-2 text-xs hidden sm:flex md:text-sm font-medium text-muted-foreground">
      <div className="flex items-center gap-1">
        <ArrowUpDown className="size-4 md:hidden" />
        <span className="hidden md:block">{__('Requests/sec:', 'wp-cloudflare-page-cache')}</span>
        <span className="text-orange-600 font-bold">{requestsPerSecond}</span>
      </div>

      <span>•</span>

      <div className="flex items-center gap-1">
        <HardDrive className="size-4 md:hidden" />
        <span className="hidden md:block">{__('Total data served:', 'wp-cloudflare-page-cache')}</span>
        <span className="text-green-600 font-bold">{formatBytes(bytes)}</span>
      </div>
    </div>
  );
};

export default CloudflareHeaderAnalytics;