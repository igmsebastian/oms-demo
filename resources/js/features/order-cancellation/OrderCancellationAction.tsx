import { router } from '@inertiajs/react';
import { XCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import type { Order } from '@/entities/order/model/types';
import { cancel } from '@/routes/orders';
import { store as requestCancellation } from '@/routes/orders/cancellation-requests';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';

type OrderCancellationActionProps = {
    order: Order;
    mode: 'cancel' | 'request';
};

export function OrderCancellationAction({
    order,
    mode,
}: OrderCancellationActionProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const label = mode === 'cancel' ? 'Cancel' : 'Request Cancellation';

    return (
        <>
            <Button
                type="button"
                variant="destructive"
                onClick={() => setOpen(true)}
            >
                <XCircle className="size-4" />
                {label}
            </Button>
            <ConfirmationDialog
                open={open}
                onOpenChange={setOpen}
                title={label}
                description={`A reason will be saved on ${order.order_number}.`}
                confirmLabel={label}
                destructive
                requireNote
                noteLabel="Reason"
                notePlaceholder="Reason for cancellation"
                processing={processing}
                onConfirm={(reason) => {
                    setProcessing(true);
                    const request =
                        mode === 'cancel'
                            ? cancel.post(order.order_number)
                            : requestCancellation.post(order.order_number);

                    router.visit(request.url, {
                        method: request.method,
                        data: { reason },
                        preserveState: true,
                        preserveScroll: true,
                        onError: () =>
                            toast.error(
                                'We could not complete the cancellation action. Please try again.',
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
