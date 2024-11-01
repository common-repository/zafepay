const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
import { ReactComponent as ZafepayIcon } from "../assets/zafepay_logo.svg";
import { ReactComponent as WebpayIcon } from "../assets/webpay.svg";
import { ReactComponent as FintocIcon } from "../assets/fintoc.svg";
import { ReactComponent as MachIcon } from "../assets/mach.svg";
import { ReactComponent as VisaIcon } from "../assets/visa.svg";
import { ReactComponent as MasterIcon } from "../assets/master.svg";
import { ReactComponent as AmexIcon } from "../assets/amex.svg";

const DisplayContent = () => {
  return (
    <div style={{ display: "flex", alignItems: "flex-end" }}>
      <VisaIcon style={{ marginRight: "7px" }} />
      <MasterIcon style={{ marginRight: "7px" }} />
      <AmexIcon style={{ marginRight: "7px" }} />
      <WebpayIcon style={{ marginRight: "7px" }} />
      <FintocIcon style={{ marginRight: "7px" }} />
      <MachIcon style={{ marginRight: "7px" }} />
    </div>
  );
};

const zafepayPaymentMethod = {
  name: "zafepay",
  label: <ZafepayIcon />,
  content: <DisplayContent />,
  edit: <div>¡Con Zafepay puedes cobrar como quieras!</div>,
  canMakePayment: () => true,
  paymentMethodId: "zafepay",
  ariaLabel: "Paga con Zafepay",
  supports: {
    features: ["products"],
  },
};

registerPaymentMethod(zafepayPaymentMethod);
