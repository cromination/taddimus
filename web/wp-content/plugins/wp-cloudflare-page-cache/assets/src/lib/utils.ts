import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"
import { LINKS } from "./constants";

/**
 * Merge class names.
 * 
 * @param inputs - The class names to merge.
 * @returns The merged class names.
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Format a date to a human readable format.
 * 
 * @param date - The date to format.
 * @returns The formatted date.
 * 
 * @example
 * formatDate('2021-01-01') // January 1, 2021
 * formatDate('2021-01-01T00:00:00Z') // January 1, 2021
 * formatDate('2021-01-01T00:00:00+00:00') // January 1, 2021
 * formatDate('2021-01-01T00:00:00-00:00') // January 1, 2021
 * formatDate('2021-01-01T00:00:00+00:00') // January 1, 2021
 */
export function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

/**
 * Get the upsell URL.
 * 
 * @param utmCampaign - The UTM campaign to use.
 * @returns The upsell URL.
 */
export function getUpsellURL(utmCampaign: string) {
  return LINKS.UPSELL.replace('replace:campaign', utmCampaign);
}

/**
 * Format bytes to a human readable format.
 * 
 * @param bytes - The number of bytes to format.
 * @returns The formatted bytes.
 * 
 * @example
 * formatBytes(1024) // 1 KB
 * formatBytes(1024 * 1024) // 1 MB
 * formatBytes(1024 * 1024 * 1024) // 1 GB
 * formatBytes(1024 * 1024 * 1024 * 1024) // 1 TB
 */
export function formatBytes(bytes: number) {
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  if (bytes === 0) return '0 Bytes';
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(2)}${i > 0 ? '' : ' '}${sizes[i]}`;
}

/**
 * Format a number to a readable format.
 * 
 * @param number - The number to format.
 * @returns The formatted number.
 * 
 * @example
 * formatNumberToReadableFormat(20) // 20
 * formatNumberToReadableFormat(100) // 100
 * formatNumberToReadableFormat(1000) // 1k
 * formatNumberToReadableFormat(1500) // 1.5k
 * formatNumberToReadableFormat(15000) // 15k
 * formatNumberToReadableFormat(150000) // 150k
 * formatNumberToReadableFormat(1500000) // 1.5M
 * formatNumberToReadableFormat(15000000) // 15M
 * formatNumberToReadableFormat(150000000) // 150M
 */
export const formatNumberToReadableFormat = (number: number) => {
  if (number >= 1000000) {
    return `${(number / 1000000).toFixed(2)}M`;
  }

  if (number >= 1000) {
    return `${(number / 1000).toFixed(2)}k`;
  }

  if (number >= 100) {
    return number.toFixed(1);
  }

  return number.toFixed(2);
};

/**
 * Get the black friday banner markup.
 * 
 * @returns The black friday banner markup or null if not found.
 */
export const getBlackFridayBannerMarkup = () => {
  return window.SPCBlackFridayBanner ? window.SPCBlackFridayBanner : null;
}