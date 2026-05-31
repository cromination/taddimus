import TransitionWrapper from "@/common/TransitionWrapper";
import Button from "@/components/Button";
import Notice from "@/components/Notice";
import { LINKS } from "@/lib/constants";
import { useSettingsStore } from "@/store/optionsStore";
import { __ } from "@wordpress/i18n";

type PageContentProps = {
  children: React.ReactNode;
}

const PageContent = ({ children }: PageContentProps) => {
  const { invalidEncryptionState } = useSettingsStore();

  return (

    <TransitionWrapper className="delay-100 grid gap-5">
      {invalidEncryptionState && (
        <Notice
          type="error"
          title={__('Cloudflare credentials need attention', 'wp-cloudflare-page-cache')}
          description={__('Super Page Cache could not decrypt your stored Cloudflare credentials. This usually means your WordPress secret keys changed.', 'wp-cloudflare-page-cache')}
        >
          <Button href={LINKS.CLOUDFLARE_SETTINGS} size="sm" variant="blue" className="rounded-sm">
            {__('Open Cloudflare settings', 'wp-cloudflare-page-cache')}
          </Button>
        </Notice>
      )}
      {children}
    </TransitionWrapper>

  );
}

export default PageContent;
