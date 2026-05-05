import * as LabelPrimitive from "@radix-ui/react-label"
import * as React from "react"

import { cn } from "@/lib/utils"
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip"

type LabelProps = React.ComponentProps<typeof LabelPrimitive.Root> & {
  required?: boolean
  requiredText?: string
}

function Label({
  className,
  children,
  required = false,
  requiredText = "Required",
  ...props
}: LabelProps) {
  return (
    <LabelPrimitive.Root
      data-slot="label"
      className={cn(
        "text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50",
        className
      )}
      {...props}
    >
      {children}
      {required && (
        <>
          {" "}
          <Tooltip>
            <TooltipTrigger asChild>
              <span
                aria-label={requiredText}
                className="inline-flex cursor-help text-destructive"
              >
                *
              </span>
            </TooltipTrigger>
            <TooltipContent>{requiredText}</TooltipContent>
          </Tooltip>
        </>
      )}
    </LabelPrimitive.Root>
  )
}

export { Label }
