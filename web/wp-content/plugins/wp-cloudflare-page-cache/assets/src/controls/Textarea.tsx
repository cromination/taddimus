import { useSettingsStore } from "@/store/optionsStore";
import TextareaControl from "@/pages/settings/controls/TextareaControl";
import { useAppStore } from "@/store/store";

type TextareaProps = {
  id: string;
  label: string | React.ReactNode;
  description?: string | React.ReactNode;
  children?: React.ReactNode;
  placeholder?: string;
}

const Textarea = (props: TextareaProps) => {
  const { updateSetting, settings, isSettingOverridden, getManagedDescription } = useSettingsStore();
  const { asyncLocked } = useAppStore();
  const { id } = props;
  const overridden = isSettingOverridden(id);

  const handleChange = (nextValue: string) => {
    updateSetting(id, nextValue);
  }

  return (
    <TextareaControl
      {...props}
      onChange={handleChange}
      value={settings[id] as string}
      disabled={asyncLocked || overridden}
      description={getManagedDescription(id, props.description)}
    />
  );
}

export default Textarea;
