import { inject } from 'vue'

// Define the context type
interface SearchContextType {
    open: boolean
    setOpen: (value: boolean) => void
}

// Injection key
const SearchContextKey = Symbol('SearchContext')

export function useSearch(): SearchContextType {
    const searchContext = inject(SearchContextKey)

    if (!searchContext) {
        throw new Error('useSearch has to be used within a SearchProvider')
    }

    return searchContext
}
