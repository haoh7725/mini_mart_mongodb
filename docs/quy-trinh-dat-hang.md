# Use case nghiệp vụ: Đặt hàng

Sơ đồ hoạt động và sơ đồ tuần tự minh họa cho use case này nằm trong file
[`docs/diagrams/quy-trinh-dat-hang.drawio`](diagrams/quy-trinh-dat-hang.drawio).

## Mô tả

Quy trình đặt hàng mô tả cách khách hàng mua sản phẩm trên website Mini Mart, từ lúc chọn
sản phẩm cho đến khi đơn hàng được giao và hoàn tất. Quy trình giúp cửa hàng ghi nhận đơn
chính xác, quản lý tồn kho kịp thời và hỗ trợ hai hình thức thanh toán: thanh toán khi nhận
hàng (COD) và thanh toán trực tuyến qua VNPay.

## Các dòng cơ bản

1. Khách hàng có nhu cầu mua thực phẩm, đồ dùng hằng ngày.
2. Khách hàng truy cập website Mini Mart, tìm sản phẩm theo danh mục, từ khóa hoặc mục giải cứu thực phẩm.
3. Khách hàng xem thông tin sản phẩm: giá, giá khuyến mãi, hình ảnh, mô tả, số lượng còn lại và đánh giá của người mua trước.
4. Khách hàng chọn số lượng và thêm sản phẩm vào giỏ hàng.
5. Hệ thống kiểm tra sản phẩm còn đang bán và còn đủ số lượng trong kho, rồi cập nhật giỏ hàng.
6. Khách hàng xem lại giỏ hàng, điều chỉnh số lượng hoặc xóa sản phẩm nếu cần.
7. Khách hàng chọn thanh toán.
8. Hệ thống hiển thị trang thanh toán. Nếu khách đã đăng nhập, hệ thống tự điền sẵn thông tin cá nhân.
9. Khách hàng nhập họ tên người nhận, số điện thoại, email, địa chỉ giao hàng (số nhà, phường/xã, tỉnh/thành) và ghi chú.
10. Khách hàng chọn phương thức thanh toán là thanh toán khi nhận hàng (COD), rồi bấm **Đặt hàng**.
11. Hệ thống kiểm tra thông tin khách đã nhập và kiểm tra lại tồn kho của từng sản phẩm.
12. Hệ thống tính tiền hàng theo giá hiện hành (giá giải cứu, giá khuyến mãi hoặc giá bán). Phí giao hàng được miễn khi tiền hàng từ 300.000đ trở lên, đơn dưới mức này chịu phí 25.000đ.
13. Hệ thống tạo đơn hàng có mã dạng `MM-yymmdd-XXXXX`, ở trạng thái chờ xác nhận và chưa thanh toán.
14. Hệ thống trừ tồn kho theo nguyên tắc hết hạn trước – xuất trước và ghi lại lịch sử xuất kho.
15. Hệ thống làm trống giỏ hàng và hiển thị trang **Đặt hàng thành công**, gồm mã đơn, tổng tiền và phương thức thanh toán.
16. Quản trị viên tiếp nhận đơn hàng, liên hệ xác nhận và chuyển đơn sang trạng thái đã xác nhận.
17. Cửa hàng chuẩn bị hàng và giao cho khách. Trạng thái đơn được cập nhật lần lượt: đang chuẩn bị → đang giao.
18. Khách hàng nhận hàng và thanh toán tiền cho nhân viên giao hàng.
19. Quản trị viên cập nhật đơn hàng sang trạng thái hoàn tất, đã thanh toán.

## Các dòng thay thế

### 1. Sản phẩm không đủ số lượng khi thêm vào giỏ

Nếu số lượng khách chọn vượt quá số lượng còn trong kho:

- Hệ thống báo lỗi "Số lượng yêu cầu vượt quá tồn kho".
- Khách hàng giảm số lượng hoặc chọn sản phẩm khác.
- Quy trình quay lại bước 4.

### 2. Giỏ hàng trống

Nếu khách chọn thanh toán khi giỏ hàng không có sản phẩm:

- Hệ thống chuyển khách về trang giỏ hàng.
- Quy trình quay lại bước 2.

### 3. Thông tin đặt hàng không hợp lệ

Nếu khách bỏ trống họ tên, số điện thoại, địa chỉ hoặc nhập sai định dạng:

- Hệ thống giữ lại thông tin đã nhập và báo lỗi tại các ô bị sai.
- Khách hàng sửa lại thông tin.
- Quy trình quay lại bước 9.

### 4. Sản phẩm hết hàng hoặc ngừng bán lúc đặt hàng

Nếu tại bước 11 có sản phẩm đã ngừng bán hoặc không còn đủ số lượng, ví dụ do khách khác mua trước:

- Hệ thống không tạo đơn và báo tên sản phẩm bị lỗi.
- Khách hàng điều chỉnh giỏ hàng.
- Quy trình quay lại bước 6.

### 5. Thanh toán trực tuyến qua VNPay

Nếu tại bước 10 khách chọn thanh toán qua VNPay:

- Hệ thống thực hiện bước 11 đến bước 14 như dòng cơ bản.
- Hệ thống chuyển khách sang trang thanh toán của VNPay. Liên kết thanh toán có hiệu lực trong 15 phút.
- Khách hàng nhập thông tin thẻ hoặc tài khoản ngân hàng và xác nhận thanh toán.
- VNPay gửi kết quả giao dịch về hệ thống. Hệ thống kiểm tra kết quả có đúng do VNPay gửi và số tiền có khớp với đơn hàng không.
- Nếu thanh toán thành công, hệ thống ghi nhận đơn **đã thanh toán** và hiển thị kết quả cho khách.
- Quy trình tiếp tục từ bước 16; bỏ qua việc thu tiền ở bước 18.

### 6. Thanh toán VNPay thất bại hoặc bị hủy

Nếu khách hủy giao dịch, giao dịch bị lỗi hoặc quá thời gian thanh toán:

- Hệ thống ghi nhận đơn **thanh toán thất bại** và thông báo cho khách.
- Quản trị viên liên hệ khách để chuyển sang thanh toán khi nhận hàng, hoặc hủy đơn.
- Nếu chuyển sang COD, quy trình tiếp tục từ bước 16. Nếu hủy, đơn chuyển sang trạng thái **đã hủy** và quy trình kết thúc.

### 7. VNPay chưa sẵn sàng

Nếu cửa hàng chưa cấu hình cổng VNPay:

- Hệ thống báo lỗi tại mục phương thức thanh toán.
- Khách hàng chọn thanh toán khi nhận hàng (COD).
- Quy trình quay lại bước 10.

### 8. Khách hàng đã đăng nhập mua sản phẩm giải cứu

Nếu khách đã đăng nhập và mua sản phẩm thuộc chương trình giải cứu thực phẩm:

- Tại bước 14, hệ thống cộng 10 điểm xanh cho mỗi sản phẩm mua từ lô giải cứu.
- Quy trình tiếp tục như dòng cơ bản.

### 9. Khách hàng không nhận hàng

Nếu khách từ chối nhận hàng hoặc không liên hệ được khi giao:

- Quản trị viên cập nhật đơn sang trạng thái **đã hủy**.
- Quy trình kết thúc.

### 10. Mua lại đơn hàng cũ

Nếu khách đã đăng nhập muốn mua lại một đơn trước đó:

- Khách hàng vào **Tài khoản → Đơn hàng** và chọn **Mua lại**.
- Hệ thống thêm các sản phẩm còn bán vào giỏ hàng (giới hạn theo tồn kho) và báo những sản phẩm bị bỏ qua.
- Quy trình tiếp tục từ bước 6.
