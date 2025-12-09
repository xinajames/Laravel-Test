function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        return new Promise((resolve, reject) => {
            timeout = setTimeout(() => {
                Promise.resolve(func.apply(this, args)).then(resolve).catch(reject);
            }, wait);
        });
    };
}

export default class DataTableService {
    static debouncedPaginate = debounce(
        function ({ route, currentPage, perPage, search, filters, orders }) {
            const params = {
                page: currentPage,
                per_page: perPage,
                'filter[search]': search,
                filters,
                orders,
            };

            return axios.get(route, { params });
        },
        300 // Debounce wait time
    );

    static paginate(args) {
        return DataTableService.debouncedPaginate(args);
    }
}
