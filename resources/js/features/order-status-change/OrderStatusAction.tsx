import { router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import type { Order, OrderStatusPayload } from '@/entities/order/model/types';
import { fulfill } from '@/routes/orders';
import { update as updateStatus } from '@/routes/orders/status';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';

type OrderStatusActionProps = {
    order: Order;
    status?: OrderStatusPayload;
    action: 'fulfill' | 'status';
    label: string;
    icon: LucideIcon;
    tone?: 'default' | 'success' | 'info';
};

export function OrderStatusAction({
    order,
    status,
    action,
    label,
    icon: Icon,
    tone = 'default',
}: OrderStatusActionProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const className =
        tone === 'success'
            ? 'bg-green-600 text-white hover:bg-green-700'
            : tone === 'info'
              ? 'bg-blue-600 text-white hover:bg-blue-700'
              : undefined;

    return (
        <>
            <Button
                type="button"
                className={className}
                onClick={() => setOpen(true)}
            >
                <Icon className="size-4" />
                {label}
            </Button>
            <ConfirmationDialog
                open={open}
                onOpenChange={setOpen}
                title={label}
                description={`Apply this change to ${order.order_number}.`}
                confirmLabel="Confirm"
                noteLabel="Note"
                notePlaceholder="Optional status note"
                processing={processing}
                onConfirm={(note) => {
                    setProcessing(true);
                    const request =
                        action === 'fulfill'
                            ? fulfill.post(order.order_number)
                            : updateStatus.patch(order.order_number);

                    router.visit(request.url, {
                        method: request.method,
                        data:
                            action === 'fulfill'
                                ? { note }
                                : { status: status?.id, note },
                        preserveState: true,
                        preserveScroll: true,
                        onError: () =>
                            toast.error(
                                'We could not update the order status. Please try again.',
                            ),
                        onFinish: () => {
                            setProcessing(false);
                            setOpen(false);
                        },
                    });
                }}
            />
        </>
    );
}
