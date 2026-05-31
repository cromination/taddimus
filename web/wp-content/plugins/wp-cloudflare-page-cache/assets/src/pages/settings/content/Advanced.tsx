import PageContent from "@/layout/PageContent";

import AdvancedCache from "./advanced/AdvancedCache";
import AdvancedCloudflare from "./advanced/AdvancedCloudflare";
import AdvancedHeartbeat from "./advanced/AdvancedHeartbeat";
import AdvancedLog from "./advanced/AdvancedLogs";
import AdvancedOthers from "./advanced/AdvancedOthers";
import AdvancedPrefetchPreconnect from "./advanced/AdvancedPrefetchPreconnect";
import AdvancedPreloader from "./advanced/AdvancedPreloader";
import AdvancedVarnish from "./advanced/AdvancedVarnish";

const Advanced = () => {
  return (
    <PageContent>
      <AdvancedCache />
      <AdvancedCloudflare />
      <AdvancedHeartbeat />
      <AdvancedPreloader />
      <AdvancedVarnish />
      <AdvancedPrefetchPreconnect />
      <AdvancedLog />
      <AdvancedOthers />
    </PageContent>
  )
}

export default Advanced;
