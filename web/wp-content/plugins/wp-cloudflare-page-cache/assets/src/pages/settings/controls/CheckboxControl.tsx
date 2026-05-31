import { Checkbox } from "@/components/ui/checkbox";

type CheckboxControlProps = {
  id: string;
  value: boolean | number;
  onChange: (nextValue: boolean, id?: string) => void;
  label: string | React.ReactNode;
  description?: string | React.ReactNode;
  disabled?: boolean;
}

const CheckboxControl = ({ id, label, description, value, onChange, disabled = false }: CheckboxControlProps) => {

  const handleChange = (nextValue) => {
    onChange(nextValue, id);
  }

  return (
    <div className="grid gap-3">
      <div className="flex items-start gap-2 text-left">
        <Checkbox
          className="cursor-pointer"
          id={id}
          checked={Boolean(value)}
          onCheckedChange={handleChange}
          disabled={disabled}
        />
        <div className="grid gap-1">
          <label htmlFor={id} className="cursor-pointer flex items-center text-sm">
            {label}
          </label>
          {description && (
            <div className="text-xs text-muted-foreground">
              {description}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default CheckboxControl;
