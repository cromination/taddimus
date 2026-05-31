import { SettingType, SettingValueType } from "@/types/globals";
import { createContext, useContext, useReducer } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const WP_CONFIG_NOTICE = __('This setting is currently managed by your wp-config.php environment.', 'wp-cloudflare-page-cache');

const transformSettings = (settings: Record<string, any>, typeSource = window.SPCDash.settings) => {
  return Object.entries(settings).reduce((acc, setting) => {
    const [key, settingData] = setting;

    // Handle case where setting data has both value and type (like window.SPCDash.settings)
    const value = settingData?.value !== undefined ? settingData.value : settingData;
    const type = settingData?.type || typeSource[key]?.type;

    if (type === 'textarea' && Array.isArray(value)) {
      acc[key] = value.join('\n');
      return acc;
    }

    if (type === 'bool') {
      acc[key] = Boolean(parseInt(value as string));
      return acc;
    }

    acc[key] = value;
    return acc;
  }, {});
};

const localizedSettings = transformSettings(window.SPCDash.settings);
const getSettingMeta = (key: string) => window.SPCDash.settings?.[key];

interface SettingsState {
  settings: SettingType;
  cloudflareConnected: boolean;
  invalidEncryptionState: boolean;
}

type SettingsAction =
  | { type: 'UPDATE_SETTING'; payload: { key: string; value: SettingValueType } }
  | { type: 'UPDATE_SETTINGS'; payload: { settings: SettingType; meta?: Partial<Pick<SettingsState, 'cloudflareConnected' | 'invalidEncryptionState'>> } };

const initialState: SettingsState = {
  settings: localizedSettings,
  cloudflareConnected: Boolean(window.SPCDash.cloudflareConnected),
  invalidEncryptionState: Boolean(window.SPCDash.invalidEncryptionState),
}

const settingsReducer = (state: SettingsState, action: SettingsAction): SettingsState => {
  switch (action.type) {
  case 'UPDATE_SETTING':
    return {
      ...state,
      settings: {
        ...state.settings,
        [action.payload.key]: action.payload.value
      }
    };
  case 'UPDATE_SETTINGS':
    return {
      ...state,
      settings: {
        ...state.settings,
        ...transformSettings(action.payload.settings)
      },
      cloudflareConnected: action.payload.meta?.cloudflareConnected ?? state.cloudflareConnected,
      invalidEncryptionState: action.payload.meta?.invalidEncryptionState ?? state.invalidEncryptionState,
    };
  default:
    return state;
  }
}

const SettingsContext = createContext<{
  state: SettingsState;
  dispatch: React.Dispatch<SettingsAction>;
}>(undefined);

const SettingsProvider = ({ children }: { children: React.ReactNode }) => {
  const [state, dispatch] = useReducer(settingsReducer, initialState);

  return (
    <SettingsContext.Provider value={{ state, dispatch }}>
      {children}
    </SettingsContext.Provider>
  );
}

const useSettingsStore = () => {
  const context = useContext(SettingsContext);
  if (context === undefined) {
    throw new Error('useSettingsStore must be used within a SettingsProvider');
  }

  const { state, dispatch } = context;

  return {
    settings: state.settings,
    invalidEncryptionState: state.invalidEncryptionState,
    pageCacheOn: Boolean(state.settings.cf_fallback_cache),
    updateSetting: (key: string, value: SettingValueType) => dispatch({ type: 'UPDATE_SETTING', payload: { key, value } }),
    updateSettings: (settings: SettingType, meta?: Partial<Pick<SettingsState, 'cloudflareConnected' | 'invalidEncryptionState'>>) => dispatch({ type: 'UPDATE_SETTINGS', payload: { settings, meta } }),
    cloudflareConnected: state.cloudflareConnected,
    getSettingMeta,
    isSettingOverridden: (key: string) => Boolean(getSettingMeta(key)?.overridden),
    getManagedDescription: (key: string, description?: React.ReactNode) => {
      if (!getSettingMeta(key)?.overridden) {
        return description;
      }

      return (
        <>
          {description}
          {description ? ' ' : ''}
          <span className="text-amber-700 dark:text-amber-400">{WP_CONFIG_NOTICE}</span>
        </>
      );
    },
    isToggleOn: (key: string) => {
      if (
        !state.settings[key] ||
        !window.SPCDash.settings[key]
      ) return false;

      return Boolean(state.settings[key]);
    },
    isValueSelected: (key: string, value: string) => {
      if (
        !state.settings[key] ||
        !window.SPCDash.settings[key]
      ) return false;
      return state.settings[key] === value;
    }
  };
}

export { SettingsProvider, useSettingsStore };
