export function usePagination() {
    function toPage(that, page = 1) {
        that.paginate(page);
    }

    function nextPage(that) {
        let currentPage = that.datatable.value.pagination.current_page;
        let totalPages = that.datatable.value.pagination.total_pages;
        if (totalPages > currentPage && !that.datatable.state.processing) {
            let page = currentPage + 1;
            that.paginate(page);
        }
    }

    function previousPage(that) {
        let currentPage = that.datatable.value.pagination.current_page;
        if (currentPage > 1 && !that.datatable.state.processing) {
            let page = currentPage - 1;
            that.paginate(page);
        }
    }

    function firstPage(that) {
        if (!that.datatable.state.processing) {
            that.paginate(1);
        }
    }

    function lastPage(that) {
        let totalPages = that.datatable.value.pagination.total_pages;
        if (!that.datatable.state.processing) {
            that.paginate(totalPages);
        }
    }

    function pageInformation(datatable) {
        let currentPage = datatable.value.pagination.current_page;
        let perPage = datatable.settings.perPage;
        let totalPages = datatable.value.pagination.total_pages;
        let totalRow = datatable.value.pagination.total;
        let pageInfo = 'page ' + currentPage + ' of ' + totalPages;
        let showingFirstRowOf = currentPage > 1 ? (currentPage - 1) * perPage + 1 : currentPage;
        let showingLastRowOf =
            currentPage > 1
                ? currentPage === totalPages
                    ? totalRow
                    : currentPage * perPage
                : currentPage + perPage - 1;
        let showing =
            ' (' + showingFirstRowOf + ' to ' + showingLastRowOf + ') of ' + totalRow + ' results';

        datatable.info.page = pageInfo;
        datatable.info.showing = showing;
    }

    return { toPage, nextPage, previousPage, firstPage, lastPage, pageInformation };
}
