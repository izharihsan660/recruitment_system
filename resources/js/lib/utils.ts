import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function toLocalDatetime(isoString?: string | null): string {
  if (!isoString) {
    return ''
  }

  const date = new Date(isoString)
  const offset = 8 * 60
  const localDate = new Date(date.getTime() + offset * 60 * 1000)

  return localDate.toISOString().slice(0, 16)
}
