<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    title: string;
    description: string;
    confirmLabel?: string;
    disabled?: boolean;
    confirmText?: string;
};

const props = withDefaults(defineProps<Props>(), {
    confirmLabel: 'Remove',
    disabled: false,
});

const emit = defineEmits<{
    confirm: [];
}>();

const open = ref(false);
const typed = ref('');

const canConfirm = computed(
    () =>
        !props.disabled &&
        (!props.confirmText || typed.value === props.confirmText),
);

watch(open, () => {
    typed.value = '';
});

const confirm = () => {
    emit('confirm');
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <div v-if="confirmText" class="grid gap-2">
                <Label for="confirm-text">
                    Type
                    <span class="font-mono font-semibold">{{
                        confirmText
                    }}</span>
                    to confirm
                </Label>
                <Input id="confirm-text" v-model="typed" autocomplete="off" />
            </div>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="!canConfirm"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
