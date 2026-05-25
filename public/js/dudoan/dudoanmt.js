document.addEventListener("DOMContentLoaded", function () {
  const loadMoreBtn = document.querySelector(".js-load-readmore-post-item");
  const listContainer = document.querySelector(".js-article-listing");

  loadMoreBtn.addEventListener("click", function () {
    const lastId = parseInt(this.getAttribute("data-last-id"));
    const mien = this.getAttribute("data-mien");

    fetch("/dudoan-load-more", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        last_id: lastId,
        mien: mien,
        type: "dd",
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success" && data.data.length > 0) {
          data.data.forEach((pred) => {
            let html = `
                        <li>
                            <a class="util-flex util-shadow-sm util-text-decoration-none util-p-4" 
                               href="/${pred.slug}"
                               title="${pred.title}">
                                <img class="article__thumb__medium util-float-start util-me-3 util-lazy-load" 
                                     src="${pred.image_url}" 
                                     alt="${pred.title}"
                                     style="display: block !important; opacity: 1 !important;">
                                <aside>
                                    <h3 class="util-fs-5-5 util-font-medium util-lh-base">${
                                      pred.title
                                    }</h3>
                                    <p class="util-text-body util-line-clamp-2">${
                                      pred.content
                                    }...</p>
                                </aside>
                            </a>
                        </li>`;
            listContainer.insertAdjacentHTML("beforeend", html);
          });
          this.setAttribute("data-last-id", data.lastId);
        } else {
          this.disabled = true;
          this.innerHTML = "Không còn dữ liệu để tải";
        }
      })
      .catch((err) => {
        console.error("Lỗi khi tải dữ liệu:", err);
      });
  });
});
