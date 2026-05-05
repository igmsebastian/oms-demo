import { router } from '@inertiajs/react';
import { Trash } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import type { Product } from '@/entities/product/model/types';
import { destroy } from '@/routes/products';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';

type ProductDeleteDialogProps = {
    product: Product;
};

export function ProductDeleteDialog({ product }: ProductDeleteDialogProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="text-destructive"
                onClick={() => setOpen(true)}
            >
                <Trash className="size-4" />
                Delete
            </Button>
            <ConfirmationDialog
                open={open}
                onOpenChange={setOpen}
                title="Delete product"
                description={`Delete ${product.name}.`}
                confirmLabel="Delete"
                destructive
                processing={processing}
                showNote={false}
                onConfirm={() => {
                    setProcessing(true);
                    const request = destroy.delete(product.id);
                    router.visit(request.url, {
                        method: request.method,
                        preserveScroll: true,
                        onError: () =>
                            toast.error(
                                'We could not delete the product. Please try again.',
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
