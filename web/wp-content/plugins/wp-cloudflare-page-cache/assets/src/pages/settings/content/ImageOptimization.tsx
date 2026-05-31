import { __ } from '@wordpress/i18n';
import { ArrowRight, FileStack, Gauge, ImageDown, Star, Zap } from 'lucide-react';
import Card, { CardContent, CardHeader } from "@/components/Card";
import { useState } from '@wordpress/element';
import { cn } from "@/lib/utils";
import Button from "@/components/Button";

const features = [
  {
    icon: Zap,
    text: __('Lossless & lossy compression modes', 'wp-cloudflare-page-cache'),
  },
  {
    icon: Gauge,
    text: __('Bulk optimization for existing images', 'wp-cloudflare-page-cache'),
  },
  {
    icon: ImageDown,
    text: __('WebP conversion for modern browsers', 'wp-cloudflare-page-cache'),
  },
  {
    icon: FileStack,
    text: __('Automatic optimization on upload', 'wp-cloudflare-page-cache'),
  },
];

export const ImageOptimization = () => {
  const [showImage, setShowImage] = useState(false);

  const onImageLoaded = () => {
    setShowImage(true);
  }

  const { logo, ratingByline, activeInstalls, thickboxURL, cta, active, show } = window.SPCDash.robinData;

  if( active || !show ) {
    return null;
  }

  return (
    <Card>
      <CardHeader className="flex items-center gap-4 bg-muted p-6">
        <div className="size-16 shrink-0 overflow-hidden rounded-md">
          <img
            onLoad={onImageLoaded}
            src={logo}
            alt={__('Robin Image Optimizer', 'wp-cloudflare-page-cache')}
            className={cn("size-16",
              showImage ? "opacity-100" : "opacity-0 transition-opacity duration-500",
            )} />
        </div>
        <div className="grid gap-0.5">
          <h2 className="m-0 text-xl font-bold leading-tight">
            {__('Optimize your images for faster loading', 'wp-cloudflare-page-cache')}
          </h2>
          <span className="text-sm text-muted-foreground">
            {__('By the Super Page Cache Pro team', 'wp-cloudflare-page-cache')}
          </span>
        </div>
      </CardHeader>

      <CardContent className="p-6">
        <div className="mb-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800/40 dark:bg-green-900/20">
          <Zap className="size-5 shrink-0 text-green-600 dark:text-green-400" />
          <p className="m-0 text-sm font-medium text-green-800 dark:text-green-200">
            {__('Unoptimized images can account for 50% of your page loading time!', 'wp-cloudflare-page-cache')}
          </p>
        </div>

        <p className="m-0 mb-5 text-sm text-muted-foreground">
          {__('Robin Image Optimizer automatically compresses your images without losing quality, helping your website load faster and rank higher in search engines. With just one click, optimize all existing and future images.', 'wp-cloudflare-page-cache')}
        </p>

        <div className="mb-6 grid grid-cols-1 gap-3 md:grid-cols-2">
          {features.map((feature) => (
            <div key={feature.text} className="flex items-center gap-3 rounded-lg bg-muted p-3">
              <feature.icon className="size-5 shrink-0 text-green-600" />
              <span className="text-sm">{feature.text}</span>
            </div>
          ))}
        </div>

        <div className="rounded-xl bg-gradient-to-r from-green-600 to-green-500 p-6 text-white">
          <div className="mb-4 text-lg font-semibold">
            {__('Robin Image Optimizer - Smart WordPress Image Compression', 'wp-cloudflare-page-cache')}
          </div>

          <div className="mb-4 flex items-center gap-2">
            <div className="flex items-center gap-1">
              {[...Array(5)].map((_, i) => (
                <Star key={i} className="size-4 fill-yellow-400 text-yellow-400" />
              ))}
            </div>
            <span className="text-sm text-green-100">{ratingByline}</span>
          </div>

          <div className="flex flex-wrap items-center justify-between gap-3">
            <span className="text-sm font-medium text-green-100">
              {activeInstalls}
            </span>

            <Button
              href={thickboxURL}
              size="lg"
              className="thickbox group border-white bg-white text-green-700 hover:bg-green-50"
            >
              {cta}
              <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

