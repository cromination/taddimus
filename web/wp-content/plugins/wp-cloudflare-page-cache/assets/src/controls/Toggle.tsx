import ToggleControl from "@/pages/settings/controls/ToggleControl";
import { useSettingsStore } from "@/store/optionsStore";
import { useAppStore } from "@/store/store";

type ToggleProps = {
  id: string;
  label: string | React.ReactNode;
  description?: string | React.ReactNode;
  children?: React.ReactNode;
  sideEffectCallback?: (value: boolean) => void;
  disabled?: boolean;
}

const Toggle = ({ id, disabled = false, ...props }: ToggleProps) => {
  const { updateSetting, settings, isSettingOverridden, getManagedDescription } = useSettingsStore();
  const { asyncLocked } = useAppStore();
  const overridden = isSettingOverridden(id);

  const handleChange = (nextValue: boolean) => {
    updateSetting(id, nextValue ? 1 : 0);

    if (props.sideEffectCallback) {
      props.sideEffectCallback(nextValue);
    }
  };

  return (
    <ToggleControl
      {...props}
      id={id}
      onChange={handleChange}
      value={settings[id] as number}
      disabled={asyncLocked || disabled || overridden}
      description={getManagedDescription(id, props.description)}
    />
  );
}

export default Toggle;
