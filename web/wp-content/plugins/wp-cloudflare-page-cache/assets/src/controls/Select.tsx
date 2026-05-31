import { useSettingsStore } from "@/store/optionsStore";
import SelectControl from "@/pages/settings/controls/SelectControl";
import { useAppStore } from "@/store/store";

type SelectProps = {
  id: string;
  label: string | React.ReactNode;
  description?: string | React.ReactNode;
  children?: React.ReactNode;
  options: { value: string; label: string }[];
  className?: string;
  disabled?: boolean;
}

const Select = ({ disabled = false, ...props }: SelectProps) => {
  const { updateSetting, settings, isSettingOverridden, getManagedDescription } = useSettingsStore();
  const { asyncLocked } = useAppStore();
  const { id } = props;
  const overridden = isSettingOverridden(id);

  const handleChange = (nextValue: string) => {
    updateSetting(id, nextValue);
  }

  return (
    <SelectControl
      {...props}
      onChange={handleChange}
      value={settings[id] as string}
      disabled={asyncLocked || disabled || overridden}
      description={getManagedDescription(id, props.description)}
    />
  );
}

export default Select; 
