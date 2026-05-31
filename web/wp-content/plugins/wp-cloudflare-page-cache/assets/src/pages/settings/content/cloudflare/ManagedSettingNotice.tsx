import { cn } from "@/lib/utils";
import { __ } from "@wordpress/i18n";

type Props = {
  show: boolean;
  message?: string;
  className?: string;
};

const ManagedSettingNotice = ({ show, message, className }: Props) => {
  if (!show) {
    return null;
  }

  return (
    <p className={cn("text-xs text-amber-700 dark:text-amber-400 mt-1.5", className)}>
      {message ?? __('This setting is currently managed by your wp-config.php environment.', 'wp-cloudflare-page-cache')}
    </p>
  );
};

export default ManagedSettingNotice;
