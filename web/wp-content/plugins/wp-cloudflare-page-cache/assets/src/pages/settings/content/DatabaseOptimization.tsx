
import ExternalLink from "@/common/ExternalLink";
import Select from "@/common/Select";
import TransitionWrapper from "@/common/TransitionWrapper";
import Button from "@/components/Button";
import Card, { CardContent } from "@/components/Card";
import PageContent from "@/layout/PageContent";
import { spcApi } from "@/lib/api";
import { useSettingsStore } from "@/store/optionsStore";
import { useAppStore } from "@/store/store";
import { useEffect, useState } from "@wordpress/element";
import { __, _n, sprintf } from "@wordpress/i18n";
import { Play } from "lucide-react";
import { toast } from "sonner";
import BaseControl from "../controls/BaseControl";
import ControlsGroup from "../controls/ControlsGroup";

type DatabaseOptimizationCounts = Record<string, number>;
type DatabaseOptimizationAction = {
  title: string;
  description: string;
  scheduleKey: string;
  actionLabel: string;
};

const DATABASE_OPTIMIZATION_ACTIONS: DatabaseOptimizationAction[] = [
  {
    title: __('Post Revisions Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Deletes old revisions across all post types (posts, pages, custom post types). WordPress saves a copy every time you edit content, and cleaning them up helps reduce database size.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'post_revision_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Auto-Draft Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Deletes all auto-draft entries across all post types (posts, pages, custom post types). Useful for removing leftover drafts WordPress creates automatically.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'auto_draft_post_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Trashed Posts Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Permanently deletes all items in the trash, across all post types (posts, pages, custom post types). Helps keep your site clean by removing deleted content right away.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'trashed_post_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Spam Comments Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Remove comments marked as spam by your spam filter or manually flagged as spam.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'spam_comment_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Trashed Comments Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Permanently deletes all comments that are currently in the trash. This helps keep your site clean by removing deleted comments right away.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'trashed_comment_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Transients Data Cleanup', 'wp-cloudflare-page-cache'),
    description: __('Deletes all transients stored in your database. Useful for freeing up space and resetting temporary data.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'all_transients_interval',
    actionLabel: __('Clean Now', 'wp-cloudflare-page-cache')
  },
  {
    title: __('Database Tables Optimization', 'wp-cloudflare-page-cache'),
    description: __('Runs the OPTIMIZE TABLE command on your database tables to defragment and reclaim unused space. Helps maintain database efficiency and reduce table overhead.', 'wp-cloudflare-page-cache'),
    scheduleKey: 'optimize_tables_interval',
    actionLabel: __('Optimize Now', 'wp-cloudflare-page-cache')
  }
];

export const DatabaseOptimization = () => {
  const { isToggleOn } = useSettingsStore();
  const databaseOptimizationEnabled = isToggleOn('database_optimization');
  const [counts, setCounts] = useState<DatabaseOptimizationCounts>({});
  const [countsLoading, setCountsLoading] = useState(false);
  
  const controls = [
    {
      id: 'database_optimization',
      type: 'toggle',
      label: __('Enable Database Optimization', 'wp-cloudflare-page-cache'),
      description: <>
        {__('Optimize your database by removing unnecessary data, such as post revisions, auto-draft posts, trashed posts, spam comments, and transients.', 'wp-cloudflare-page-cache')}
        {' '}
        <ExternalLink url="https://docs.themeisle.com/super-page-cache/database-optimization">
          {__('More Info', 'wp-cloudflare-page-cache')}
        </ExternalLink>
      </>,
    },
  ];

  const loadCounts = async () => {
    setCountsLoading(true);

    const response = await spcApi.getDatabaseOptimizationCounts();

    if (response.success && response.data?.counts) {
      setCounts(response.data.counts as DatabaseOptimizationCounts);
    }

    setCountsLoading(false);
  };

  useEffect(() => {
    if (!databaseOptimizationEnabled) {
      return;
    }

    loadCounts();
  }, [databaseOptimizationEnabled]);

  return (
    <PageContent>
      <Card>
        <CardContent className="p-0 divide-y divide-border">
          <ControlsGroup controls={controls} />

          {databaseOptimizationEnabled && (
            <TransitionWrapper className="divide-y divide-foreground/10">
              {DATABASE_OPTIMIZATION_ACTIONS.map((action) => (
                <DatabaseOptimizationItem
                  key={action.scheduleKey}
                  title={action.title}
                  description={action.description}
                  scheduleKey={action.scheduleKey}
                  actionLabel={action.actionLabel}
                  count={counts[action.scheduleKey]}
                  countsLoading={countsLoading}
                  onActionComplete={loadCounts}
                />
              ))}
            </TransitionWrapper>
          )}
        </CardContent>
      </Card>
    </PageContent>
  );
}

type DatabaseOptimizationItemProps = {
  title: string;
  description: string;
  scheduleKey: string;
  actionLabel: string;
  count?: number;
  countsLoading: boolean;
  onActionComplete: () => Promise<void>;
};

const DatabaseOptimizationItem = ({ title, description, scheduleKey, actionLabel, count, countsLoading, onActionComplete }: DatabaseOptimizationItemProps) => {
  const { asyncLocked, lockAsync } = useAppStore();
  const { settings, updateSetting, isSettingOverridden, getManagedDescription } = useSettingsStore();
  const [loading, setLoading] = useState(false);
  const scheduleManaged = isSettingOverridden(scheduleKey);
  const isTableOptimization = scheduleKey === 'optimize_tables_interval';
  const isZeroCount = !isTableOptimization && count === 0;

  const handleScheduleChange = ( value: string ) => {
    updateSetting(scheduleKey, value);
  };

  const optimizeDatabase = async () => {
    lockAsync(true);
    setLoading(true);

    const data = {
      action: scheduleKey,
    }

    const response = await spcApi.databaseOptimization( data );

    if (!response.success) {
      toast.error(response.message);
    } else {
      toast.success(response.message);
      await onActionComplete();
    }

    lockAsync(false);
    setLoading(false);
  };

  const countLabel = (() => {
    if (isTableOptimization) {
      return null;
    }

    if (isZeroCount) {
      return __('Nothing to clean', 'wp-cloudflare-page-cache');
    }

    if (countsLoading && typeof count !== 'number') {
      return __('Checking items...', 'wp-cloudflare-page-cache');
    }

    if (typeof count !== 'number') {
      return null;
    }

    return sprintf(
      _n('%d item available', '%d items available', count, 'wp-cloudflare-page-cache'),
      count
    );
  })();


  const scheduleOptions = Object.entries(window.SPCDash.databaseOptimizationScheduleOptions).map(([key, value]) => ({
    label: value,
    value: key,
  }));


  return (
    <>
      <BaseControl
        label={title}
        description={getManagedDescription(scheduleKey, description)}
        afterTitle={
          <div className="flex items-center gap-3">
            {countLabel && (
              <span className="text-sm text-muted-foreground">
                {countLabel}
              </span>
            )}

            <Button
              variant="outline"
              size="sm"
              onClick={optimizeDatabase}
              disabled={asyncLocked || isZeroCount}
              loader={loading}
              icon={Play}
            >
              {loading ? __('Running', 'wp-cloudflare-page-cache') + '...' : actionLabel}
            </Button>
          </div>
        }
      >
        <div className="flex items-center space-x-2">
          <label htmlFor={scheduleKey} className="text-sm text-foreground">{__('Schedule:', 'wp-cloudflare-page-cache')}</label>
          <Select
            id={scheduleKey}
            value={settings[scheduleKey]?.toString() || 'never'}
            onChange={handleScheduleChange}
            disabled={asyncLocked || scheduleManaged}
            options={scheduleOptions}
            className="min-w-[100px]"/>
        </div>
      </BaseControl>
    </>
  );
};
