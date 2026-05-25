const puppeteer = require("puppeteer");
const mysql = require("mysql2/promise");
const fs = require("fs").promises;
const getDbConnection = require("../db.js");
(async () => {
  // Kết nối database
   let db;
   try {
     db = await getDbConnection();
   } catch (err) {
    //  console.error("Lỗi kết nối DB:", err.message);
     return;
   }

  let browser;
  let page;
  const maxRetries = 3;
  let retries = 0;
  const maxDraws = 10; // Giới hạn số kỳ cào (có thể thay đổi)

  try {
    while (retries < maxRetries) {
      try {
        browser = await puppeteer.launch({
          headless: true,
          args: [
            "--no-sandbox",
            "--disable-setuid-sandbox",
            "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
          ],
        });

        page = await browser.newPage();
        await page.setExtraHTTPHeaders({
          Accept:
            "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
          "Accept-Language": "en-US,en;q=0.5",
          Referer: "https://atrungroi.com/",
        });

        // console.log("Đang mở trang Max 3D...");
        await page.goto("https://atrungroi.com/vietlott-max-3d.html", {
          waitUntil: "networkidle2",
          timeout: 60000,
        });

        // Chờ thêm 10 giây để dữ liệu động tải
        await new Promise((resolve) => setTimeout(resolve, 10000));

        // console.log("Chờ selector .kqxs...");
        try {
          await page.waitForSelector(".kqxs", { timeout: 60000 });
        } catch (e) {
          // console.error("Không tìm thấy selector .kqxs");
          const html = await page.content();
          await fs.writeFile("debug.html", html);
          // console.log("Đã lưu HTML vào debug.html");
          throw e;
        }

        const results = await page.evaluate((maxDraws) => {
          const resultList = [];
          const blocks = document.querySelectorAll(
            ".kqxs .p-3[data-id], .kqxs .p-3.bg-dark-vietlott[data-id]"
          );
          // console.log("Số block tìm thấy:", blocks.length);

          // Giới hạn số kỳ cào
          const blocksToProcess = Array.from(blocks).slice(0, maxDraws);

          blocksToProcess.forEach((block, blockIndex) => {
            const header = block.querySelector(
              "h3.text-center.fw-bold.fs-6.mb-3"
            );
            if (!header) {
              // console.log(
              //   `Block ${blockIndex}: Không tìm thấy tiêu đề kỳ quay`
              // );
              return;
            }

            // Lấy kỳ và ngày
            const headerText = header.innerText.trim();
            const match = headerText.match(
              /#(\d+),\s*Thứ\s*\w+\s*ngày\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/
            );
            if (!match) {
              // console.log(
              //   `Block ${blockIndex}: Không khớp định dạng kỳ quay:`,
              //   headerText
              // );
              return;
            }

            const draw_number = `#${match[1]}`;
            const draw_date = `${match[4]}-${match[3].padStart(
              2,
              "0"
            )}-${match[2].padStart(2, "0")} 18:00:00`;

            // Khởi tạo dữ liệu cho bản ghi
            const result = {
              draw_type: "MAX3D",
              draw_number,
              draw_date,
              jackpot_numbers: "",
              first_numbers: "",
              second_numbers: "",
              third_numbers: "",
              jackpot_winners: "0",
              first_winners: "0",
              second_winners: "0",
              third_winners: "0",
              fourth_winners: "0",
              fifth_winners: "0",
              sixth_winners: "0",
            };

            // Lấy dữ liệu từ bảng
            const rows = block.querySelectorAll("table tbody tr");
            if (rows.length === 0) {
              // console.log(`Kỳ ${draw_number}: Không tìm thấy bảng kết quả`);
              return;
            }

            rows.forEach((row, index) => {
              const cols = row.querySelectorAll("td");
              // console.log(`Kỳ ${draw_number}, Hàng ${index}:`);
              // console.log(
              //   `  Cột 1: ${cols[0]?.innerText.trim() || "Không có"}`
              // );
              // console.log(
              //   `  Cột 2: ${cols[1]?.innerText.trim() || "Không có"}`
              // );
              // console.log(
              //   `  Cột 3: ${cols[2]?.innerText.trim() || "Không có"}`
              // );

              // Xử lý Max 3D (cột 2, giải Đặc biệt, Nhất, Nhì, Ba)
              if (index < 4) {
                if (cols.length < 3) {
                  // console.log(
                  //   `Kỳ ${draw_number}, Hàng ${index}: Không đủ cột cho Max 3D`
                  // );
                  return;
                }

                const prizeText =
                  cols[0]
                    .querySelector("span.fw-bold")
                    ?.innerText.trim()
                    .toUpperCase() || "";
                if (!prizeText) {
                  // console.log(
                  //   `Kỳ ${draw_number}, Hàng ${index}: Không tìm thấy prizeText`
                  // );
                  return;
                }

                // Lấy số lượng người trúng
                const winnerText = cols[0].innerText.match(/:\s*(\d+)/);
                const winner_count = winnerText ? parseInt(winnerText[1]) : 0;

                // Lấy số trúng
                const numberSpans = cols[1].querySelectorAll(
                  "span.js-number-max3d"
                );
                const numbers = Array.from(numberSpans)
                  .map((s) => s.innerText.trim())
                  .filter((num) => num && /^\d{3}$/.test(num))
                  .join(",");

                // Gán dữ liệu Max 3D
                if (prizeText.includes("ĐẶC BIỆT")) {
                  result.jackpot_numbers = numbers;
                  result.jackpot_winners = winner_count.toString();
                } else if (prizeText.includes("NHẤT")) {
                  result.first_numbers = numbers;
                  result.first_winners = winner_count.toString();
                } else if (prizeText.includes("NHÌ")) {
                  result.second_numbers = numbers;
                  result.second_winners = winner_count.toString();
                } else if (prizeText.includes("BA")) {
                  result.third_numbers = numbers;
                  result.third_winners = winner_count.toString();
                }
              }

              // Xử lý Max 3D+ (cột 3, giải Đặc biệt, Nhất, Nhì, Ba, Tư, Năm, Sáu)
              if (index < 7) {
                const colIndex = index < 4 ? 2 : 1;
                if (cols.length < colIndex + 1) {
                  // console.log(
                  //   `Kỳ ${draw_number}, Hàng ${index}: Không đủ cột cho Max 3D+`
                  // );
                  return;
                }

                const prizeText =
                  cols[colIndex]
                    .querySelector("span.fw-bold")
                    ?.innerText.trim()
                    .toUpperCase() || "";
                const winnerText = cols[colIndex].innerText.match(/:\s*(\d+)/);
                const winner_count = winnerText ? parseInt(winnerText[1]) : 0;

                if (prizeText.includes("ĐẶC BIỆT")) {
                  result.jackpot_winners =
                    result.jackpot_winners === "0"
                      ? winner_count.toString()
                      : `${result.jackpot_winners},${winner_count}`;
                } else if (prizeText.includes("NHẤT")) {
                  result.first_winners =
                    result.first_winners === "0"
                      ? winner_count.toString()
                      : `${result.first_winners},${winner_count}`;
                } else if (prizeText.includes("NHÌ")) {
                  result.second_winners =
                    result.second_winners === "0"
                      ? winner_count.toString()
                      : `${result.second_winners},${winner_count}`;
                } else if (prizeText.includes("BA")) {
                  result.third_winners =
                    result.third_winners === "0"
                      ? winner_count.toString()
                      : `${result.third_winners},${winner_count}`;
                } else if (prizeText.includes("TƯ")) {
                  result.fourth_winners = winner_count.toString();
                } else if (prizeText.includes("NĂM")) {
                  result.fifth_winners = winner_count.toString();
                } else if (prizeText.includes("SÁU")) {
                  result.sixth_winners = winner_count.toString();
                }
              }
            });

            // Chỉ thêm vào danh sách nếu có ít nhất một giải có số
            if (
              result.jackpot_numbers ||
              result.first_numbers ||
              result.second_numbers ||
              result.third_numbers
            ) {
              resultList.push(result);
            }
          });

          return resultList;
        }, maxDraws);

        // console.log(`Tổng số kết quả lấy được: ${results.length}`);
        if (results.length === 0) {
          // console.log("Không tìm thấy kết quả Max 3D hợp lệ.");
          const html = await page.content();
          await fs.writeFile("debug.html", html);
          // console.log("Đã lưu HTML vào debug.html");
        } else {
          // console.log("Dữ liệu cào được:", JSON.stringify(results, null, 2));
        }

        // Lưu vào DB
        for (const r of results) {
          try {
            const [existing] = await db.execute(
              "SELECT id FROM max_results WHERE draw_number = ? AND draw_type = ?",
              [r.draw_number, r.draw_type]
            );

            if (existing.length === 0) {
              await db.execute(
                `INSERT INTO max_results (
                  draw_type, draw_number, draw_date,
                  jackpot_numbers, first_numbers, second_numbers, third_numbers,
                  jackpot_winners, first_winners, second_winners, third_winners,
                  fourth_winners, fifth_winners, sixth_winners
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                  r.draw_type,
                  r.draw_number,
                  r.draw_date,
                  r.jackpot_numbers || "",
                  r.first_numbers || "",
                  r.second_numbers || "",
                  r.third_numbers || "",
                  r.jackpot_winners,
                  r.first_winners,
                  r.second_winners,
                  r.third_winners,
                  r.fourth_winners,
                  r.fifth_winners,
                  r.sixth_winners,
                ]
              );
              // console.log(`✅ Đã lưu: ${r.draw_number} - ${r.draw_type}`);
            } else {
              // console.log(`⏭️ Đã tồn tại: ${r.draw_number} - ${r.draw_type}`);
            }
          } catch (err) {
            // console.error(
            //   `❌ Lỗi lưu kỳ ${r.draw_number} - ${r.draw_type}:`,
            //   err.message
            // );
          }
        }

        break;
      } catch (e) {
        // console.error("Lỗi:", e.message);
        retries++;
        if (retries < maxRetries) {
          // console.log("Thử lại lần", retries + 1);
        } else {
          // console.log("Hủy bỏ sau 3 lần thử");
          if (page) {
            const html = await page.content();
            await fs.writeFile("debug.html", html);
            // console.log("Đã lưu HTML vào debug.html");
          }
        }
      } finally {
        if (page) await page.close();
        if (browser) {
          await browser.close();
          browser = null;
        }
      }
    }
  } catch (e) {
    // console.error("Lỗi chính:", e.message);
  } finally {
    if (browser) await browser.close();
    if (db) await db.end();
  }
})();
