import { create } from 'zustand';
import type { Product } from '@/entities/product/model/types';

type ProductSheetState = {
    open: boolean;
    product: Product | null;
    openCreate: () => void;
    openEdit: (product: Product) => void;
    close: () => void;
};

export const useProductSheetStore = create<ProductSheetState>((set) => ({
    open: false,
    product: null,
    openCreate: () => set({ open: true, product: null }),
    openEdit: (product) => set({ open: true, product }),
    close: () => set({ open: false, product: null }),
}));
