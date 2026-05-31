import { BookOpen, ExternalLink } from "lucide-react";
import { __ } from "@wordpress/i18n";
import Button from "@/components/Button";
import { LINKS } from "@/lib/constants";

const { help } = window.SPCDash;

const BrowseDocsCTA = () => {
  if (help?.popular?.length || help?.categories?.length) {
    return null;
  }

  return (
    <div className="mb-12 bg-gradient-to-r from-orange-900/10 to-amber-900/10 border border-orange-200 dark:border-orange-900/20 rounded-lg p-8 text-center">
      <BookOpen className="w-12 h-12 mx-auto mb-4 text-orange-600" />

      <h3 className="text-lg font-semibold mb-2">
        {__('Browse our documentation', 'wp-cloudflare-page-cache')}
      </h3>

      <p className="text-muted-foreground mb-6">
        {__('Setup guides, troubleshooting, and best practices live in our documentation site.', 'wp-cloudflare-page-cache')}
      </p>

      <Button variant="orange" size="lg" href={LINKS.DOCS} target="_blank">
        <ExternalLink />
        {__('Open Documentation', 'wp-cloudflare-page-cache')}
      </Button>
    </div>
  )
}

export default BrowseDocsCTA;
