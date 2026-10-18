import type { Component } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

export interface NavItem {
    label: string;
    to: RouteLocationRaw;
    icon: Component;
}

export interface ShellUser {
    name: string;
    email: string;
}
