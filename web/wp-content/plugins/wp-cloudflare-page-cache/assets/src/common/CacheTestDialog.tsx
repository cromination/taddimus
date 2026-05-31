import Button from "@/components/Button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { cn } from "@/lib/utils";
import { useAppStore } from "@/store/store";
import { useCallback } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { CircleCheck, Info } from "lucide-react";
import StatusDialog from "./StatusDialog";

type CacheTestDialogProps = {
  onClose: () => void;
  data: Record<string, any>;
};

const CacheTestDialog = ({ onClose, data }: CacheTestDialogProps) => {
  const { darkMode } = useAppStore();
  const { i18n } = window.SPCDash;
  const classes = cn('antialiased gap-0', { 'dark': darkMode });

  if (!data?.success || !data?.data) {
    return <StatusDialog onClose={onClose} success={data.success} description={data?.message || i18n.genericError} />
  }

  const ModalContent = useCallback(() => {
    const testData = data.data;

    const statuses = [
      {
        status: testData.cloudflare?.status === 'success',
        message: testData.cloudflare?.message,
        actionText: testData.cloudflare?.action_text,
        actionLink: testData.cloudflare?.action_link,
      },
      {
        status: testData.disk_cache?.status === 'success',
        message: testData.disk_cache?.message,
        actionText: testData.disk_cache?.action_text,
        actionLink: testData.disk_cache?.action_link,
      }
    ];

    return (
      <ul className="text-foreground space-y-2">
        {Object.entries(statuses).map(([key, { status, message, actionText, actionLink }]) => {
          const Icon = status ? CircleCheck : Info;

          return (
            <li key={key} className="flex items-center gap-2 text-sm">
              <Icon className={cn("flex items-center justify-center p-1 w-7 h-7 rounded-full flex-shrink-0", {
                'bg-green-500/20 text-green-600': status,
                'bg-orange-500/20 text-orange-600': !status
              })} />

              <span className="font-medium">
                {message}
                {!status && actionLink && actionText && (
                  <>{' '}<a
                    href={actionLink}
                    className="text-[#c0712f] hover:underline"
                    {...(actionLink.startsWith('http') && !actionLink.startsWith(window.location.origin)
                      ? { target: '_blank', rel: 'noopener noreferrer' }
                      : {}
                    )}
                  >{actionText}</a></>
                )}
              </span>
            </li>
          );
        })
        }
      </ul>
    );
  }, [data.data]);

  return (
    <Dialog open={true} onOpenChange={onClose}>
      <DialogContent className={classes}>

        <DialogHeader className="mb-5">
          <DialogTitle>
            {__('Status', 'wp-cloudflare-page-cache')}
          </DialogTitle>
        </DialogHeader>

        <ModalContent />

        <DialogDescription className="sr-only">
          {__('Status', 'wp-cloudflare-page-cache')}
        </DialogDescription>

        <DialogFooter className="mt-5">
          <DialogClose asChild onClick={onClose}>
            <Button type="button" variant={"orange"}>
              {i18n.close}
            </Button>
          </DialogClose>
        </DialogFooter>

      </DialogContent>
    </Dialog >
  )
}

export default CacheTestDialog;