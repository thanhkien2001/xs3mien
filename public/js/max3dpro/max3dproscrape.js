const puppeteer = require("puppeteer");
const mysql = require("mysql2/promise");
const fs = require("fs").promises;
const getDbConnection = require("../db.js");
(async () => {
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
  const maxDraws = 10; 

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

        // console.log("Đang mở trang Max 3D Pro...");
        await page.goto(
          "https://atrungroi.com/xo-so-tu-chon-vietlott-max-3d-pro.html",
          {
            waitUntil: "networkidle2",
            timeout: 60000,
          }
        );

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
            ".kqxs.p-0[data-max3d-pro-id] .p-3, .kqxs.p-0[data-max3d-pro-id] .p-3.bg-dark-vietlott"
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
              //   `Block ${blockIndex}: Không khớp định dạng kỳ quay: ${headerText}`
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
              draw_type: "MAX3DPRO",
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
              secondary_prize_winners: "0",
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
              if (cols.length < 3) {
                // console.log(`Kỳ ${draw_number}, Hàng ${index}: Không đủ cột`);
                return;
              }

              const prizeText = cols[0].innerText.trim().toUpperCase();
              const winner_count = cols[2].innerText.trim();
              let numbers = "";

              // Lấy số trúng từ cột kết quả (cho giải Đặc biệt, Nhất, Nhì, Ba)
              if (index < 4) {
                const numberSpans = cols[1].querySelectorAll("span");
                numbers = Array.from(numberSpans)
                  .map((s) => s.innerText.trim())
                  .filter((num) => num && /^\d{3}$/.test(num))
                  .join(",");
              }

              // Gán dữ liệu theo hạng giải
              if (prizeText.includes("ĐẶC BIỆT")) {
                result.jackpot_numbers = numbers;
                result.jackpot_winners = winner_count;
              } else if (prizeText.includes("NHẤT")) {
                result.first_numbers = numbers;
                result.first_winners = winner_count;
              } else if (prizeText.includes("NHÌ")) {
                result.second_numbers = numbers;
                result.second_winners = winner_count;
              } else if (prizeText.includes("BA")) {
                result.third_numbers = numbers;
                result.third_winners = winner_count;
              } else if (prizeText.includes("PHỤ")) {
                result.secondary_prize_winners = "N/A";
                result.secondary_prize_winners = winner_count;
              } else if (prizeText.includes("TƯ")) {
                result.fourth_winners = winner_count;
              } else if (prizeText.includes("NĂM")) {
                result.fifth_winners = winner_count;
              } else if (prizeText.includes("SÁU")) {
                result.sixth_winners = winner_count;
              }

              // console.log(`Kỳ ${draw_number}, Hàng ${index}:`);
              // console.log(`  Giải: ${prizeText}`);
              // console.log(`  Số: ${numbers || "N/A"}`);
              // console.log(`  SL trúng: ${winner_count}`);
            });

            // Chỉ thêm vào danh sách nếu có ít nhất một giải có số
            if (
              result.jackpot_numbers ||
              result.first_numbers ||
              result.second_numbers ||
              result.third_numbers
            ) {
              resultList.push(result);
            } else {
              // console.log(
              //   `Kỳ ${draw_number}: Không có số trúng hợp lệ, bỏ qua`
              // );
            }
          });

          return resultList;
        }, maxDraws);

        // console.log(`Tổng số kết quả lấy được: ${results.length}`);
        if (results.length === 0) {
          // console.log("Không tìm thấy kết quả Max 3D Pro hợp lệ.");
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
                  jackpot_winners, first_winners, second_winners,
                  third_winners, secondary_prize_winners, fourth_winners, fifth_winners, sixth_winners
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
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
                  r.secondary_prize_winners,
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
